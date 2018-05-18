<?php
/**
 * Ticket booking tixmall.com
 * @version v1.0b1
 */
require_once __DIR__ . '/../../config.inc.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../lib/database.php';
require_once '../lib/vendors/Inflector.php';
require_once '../lib/core.php';
require_once '../lib/constants.php';
require_once '../lib/vendors/OAuth2/Autoloader.php';
//Settings define
require_once '../lib/settings.php';
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
Paginator::currentPageResolver(function ($pageName)
{
    return empty($_GET[$pageName]) ? 1 : $_GET[$pageName];
});
$config = ['settings' => ['displayErrorDetails' => R_DEBUG]];
$app = new Slim\App($config);
//ACL
require_once '../lib/acl.php';
/**
 * GET oauthGet
 * Summary: Get site token
 * Notes: oauth
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/oauth/token', function ($request, $response, $args)
{
    $post_val = array(
        'grant_type' => 'client_credentials',
        'client_id' => OAUTH_CLIENT_ID,
        'client_secret' => OAUTH_CLIENT_SECRET
    );
    $response = getToken($post_val);
    return renderWithJson($response);
});
/**
 * GET oauthRefreshTokenGet
 * Summary: Get site refresh token
 * Notes: oauth
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/oauth/refresh_token', function ($request, $response, $args)
{
    $post_val = array(
        'grant_type' => 'refresh_token',
        'refresh_token' => $_GET['token'],
        'client_id' => OAUTH_CLIENT_ID,
        'client_secret' => OAUTH_CLIENT_SECRET
    );
    $response = getToken($post_val);
    return renderWithJson($response);
});
/**
 * GET usersLogoutGet
 * Summary: User Logout
 * Notes: oauth
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/users/logout', function ($request, $response, $args)
{
    if (!empty($_GET['token'])) {
        try {
            $oauth = Models\OauthAccessToken::where('access_token', $_GET['token'])->delete();
            $result = array(
                'status' => 'success',
            );
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson(array() , 'Please verify in your token', '', 1);
        }
    }
});
/**
 * POST userSocialLoginPost
 * Summary: User Social Login
 * Notes:  Social Login
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/users/social_login', function ($request, $response, $args)
{
    $body = $request->getParsedBody();
    $result = array();
    if (!empty($_GET['type'])) {
        $response = social_auth_login($_GET['type'], $body);
        return renderWithJson($response);
    } else {
        return renderWithJson($result, 'Please choose one provider.', '', 1);
    }
});
/**
 * Get userSocialLoginGet
 * Summary: Social Login for twitter
 * Notes: Social Login for twitter
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/users/social_login', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    if (!empty($queryParams['type'])) {
        $response = social_auth_login($queryParams['type']);
        return renderWithJson($response);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
/**
 * DELETE ContactsContactIdDelete
 * Summary: DELETE contact Id by admin
 * Notes: DELETE contact Id by admin
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/contacts/{contactId}', function ($request, $response, $args)
{
    $result = array();
    $contact = Models\Contact::find($request->getAttribute('contactId'));
    try {
        $contact->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Contact could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteContact'));
/**
 * GET ContactsGet
 * Summary: Get  contact lists
 * Notes: Get contact lists
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/contacts', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $contact = new Models\Contact;
    $validationErrorFields = $contact->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $contact->{$key} = $arg;
        }
        $contact->ip_id = saveIp();
        try {
            $contact->save();
            $contact_list = Models\Contact::with('ip')->where('id', $contact->id)->first();
            $emailFindReplace = array(
                '##FIRST_NAME##' => $contact_list['first_name'],
                '##LAST_NAME##' => $contact_list['last_name'],
                '##FROM_EMAIL##' => $contact_list['email'],
                '##IP##' => $contact_list['ip']['ip'],
                '##TELEPHONE##' => $contact_list['phone'],
                '##MESSAGE##' => $contact_list['message'],
                '##SUBJECT##' => $contact_list['subject']
            );
            sendMail('contactus', $emailFindReplace, SITE_CONTACT_EMAIL);
            $result = $contact->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Contact user could not be added. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Contact could not be added. Please, try again.', $validationErrorFields, 1);
    }
});
/**
 * GET ContactscontactIdGet
 * Summary: get particular contact details
 * Notes: get particular contact details
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/contacts/{contactId}', function ($request, $response, $args)
{
    $result = array();
    $contact = Models\Contact::find($request->getAttribute('contactId'));
    if (!empty($contact)) {
        $result['data'] = $contact->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewContact'));
$app->GET('/api/v1/contacts', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $contacts = Models\Contact::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $contacts['data'];
        unset($contacts['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $contacts
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canListContact'));
/**
 * POST LanguagePost
 * Summary: add language
 * Notes: add language
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/languages', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $language = new Models\Language;
    $validationErrorFields = $language->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $language->{$key} = $arg;
        }
        $language->slug = Inflector::slug(strtolower($language->name) , '-');
        try {
            $language->save();
            $result = $language->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Language user could not be added. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Language could not be added. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canCreateLanguage'));
/**
 * DELETE LanguageLanguageIdDelete
 * Summary: DELETE language by its id
 * Notes: DELETE language.
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/languages/{languageId}', function ($request, $response, $args)
{
    $result = array();
    $language = Models\Language::find($request->getAttribute('languageId'));
    try {
        $language->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Language could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteLanguage'));
/**
 * GET LanguageGet
 * Summary: Filter  language
 * Notes: Filter language.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/languages', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $language = Models\Language::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $language['data'];
        unset($language['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $language
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * PUT LanguagelanguageIdPut
 * Summary: Update language by admin
 * Notes: Update language by admin
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/languages/{languageId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $language = Models\Language::find($request->getAttribute('languageId'));
    $validationErrorFields = $language->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $language->{$key} = $arg;
        }
        $language->slug = Inflector::slug(strtolower($language->name) , '-');
        try {
            $language->save();
            $result['data'] = $language->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Language could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Language could not be added. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canUpdateLanguage'));
/**
 * GET LanguagelanguageIdGet
 * Summary: Get particular language
 * Notes: Get particular language.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/languages/{languageId}', function ($request, $response, $args)
{
    $result = array();
    $language = Models\Language::find($request->getAttribute('languageId'));
    if (!empty($language)) {
        $result['data'] = $language->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'Language not found', '', 1);
    }
})->add(new ACL('canViewLanguage'));
/**
 * POST pagePost
 * Summary: Create New page
 * Notes: Create page.
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/pages', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $page = new Models\Page;
    $validationErrorFields = $page->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $page->{$key} = $arg;
        }
        $page->slug = Inflector::slug(strtolower($page->title) , '-');
        try {
            $page->save();
            $result = $page->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Page user could not be added. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Page could not be added. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canCreatePage'));
/**
 * DELETE PagepageIdDelete
 * Summary: DELETE page by admin
 * Notes: DELETE page by admin
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/pages/{pageId}', function ($request, $response, $args)
{
    $result = array();
    $page = Models\Page::find($request->getAttribute('pageId'));
    try {
        $page->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Page could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeletePage'));
/**
 * GET PagesGet
 * Summary: Filter  pages
 * Notes: Filter pages.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/pages', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $pages = Models\Page::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $pages['data'];
        unset($pages['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $pages
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * PUT PagepageIdPut
 * Summary: Update page by admin
 * Notes: Update page by admin
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/pages/{pageId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $page = Models\Page::find($request->getAttribute('pageId'));
    $validationErrorFields = $page->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $page->{$key} = $arg;
        }
        $page->slug = Inflector::slug(strtolower($page->title) , '-');
        try {
            $page->save();
            $result['data'] = $page->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Page could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Page could not be updated. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canUpdatePage'));
/**
 * GET PagePageIdGet.
 * Summary: Get page.
 * Notes: Get page.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/pages/{pageId}', function ($request, $response, $args)
{
    $result = array();
    $page = Models\Page::find($request->getAttribute('pageId'));
    if (!empty($page)) {
        $result['data'] = $page->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'Language not found', '', 1);
    }
});
/**
 * POST citiesPost
 * Summary: create new city
 * Notes: create new city
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/cities', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $city = new Models\City;
    $validationErrorFields = $city->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $city->{$key} = $arg;
        }
        $city->slug = Inflector::slug(strtolower($city->name) , '-');
        try {
            $city->save();
            $result = $city->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'City could not be added. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'city could not be added. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canCreateCity'));
/**
 * DELETE CitiesCityIdDelete
 * Summary: DELETE city by admin
 * Notes: DELETE city by admin
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/cities/{cityId}', function ($request, $response, $args)
{
    $result = array();
    $city = Models\City::find($request->getAttribute('cityId'));
    try {
        $city->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'City could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteCity'));
/**
 * GET CitiesGet
 * Summary: Filter  cities
 * Notes: Filter cities.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/cities', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $cities = Models\City::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $cities['data'];
        unset($cities['data']);
        if ($queryParams[filter] == 'all') {
            $data = Models\City::all()->toArray();
        }
        $result = array(
            'data' => $data,
            '_metadata' => $cities
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * PUT CitiesCityIdPut
 * Summary: Update city by admin
 * Notes: Update city by admin
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/cities/{cityId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $city = Models\City::find($request->getAttribute('cityId'));
    $validationErrorFields = $city->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $city->{$key} = $arg;
        }
        $city->slug = Inflector::slug(strtolower($city->name) , '-');
        try {
            $city->save();
            $result['data'] = $city->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'City could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'City could not be updated. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canUpdateCity'));
/**
 * GET CitiesGet
 * Summary: Get  particular city
 * Notes: Get  particular city
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/cities/{cityId}', function ($request, $response, $args)
{
    $result = array();
    $city = Models\City::find($request->getAttribute('cityId'));
    if (!empty($city)) {
        $result['data'] = $city->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewCity'));
/**
 * POST userUserIdUserCashWithdrawals.
 * Summary: Create user cash withdrawals.
 * Notes: Create user cash withdrawals.
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/users/{userId}/user_cash_withdrawals', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $userCashWithdrawal = new Models\UserCashWithdrawals;
    $validationErrorFields = $userCashWithdrawal->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $userCashWithdrawal->{$key} = $arg;
        }
        $userCashWithdrawal->user_id = $request->getAttribute('userId');
        try {
            $userCashWithdrawal->save();
            $result = $userCashWithdrawal->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'User cash withdrawals could not be added. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'User cash withdrawals could not be added. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canCreateUserCashWithdrawals'));
/**
 * GET useruserIdUserCashWithdrawalsGet
 * Summary: Get user cash withdrawals
 * Notes: Get ruser cash withdrawals
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/users/{userId}/user_cash_withdrawals', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $userCashWithdrawals = Models\UserCashWithdrawals::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $userCashWithdrawals['data'];
        unset($userCashWithdrawals['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $userCashWithdrawals
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListUserCashWithdrawals'));
/**
 * GET useruserIdUserCashWithdrawalsUserCashWithdrawalsIdGet
 * Summary: Get paticular user cash withdrawals
 * Notes:  Get paticular user cash withdrawals
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/users/{userId}/user_cash_withdrawals/{userCashWithdrawalsId}', function ($request, $response, $args)
{
    $userCashWithdrawal = Models\UserCashWithdrawals::find($request->getAttribute('userCashWithdrawalsId'));
    if (!empty($userCashWithdrawal)) {
        $result['data'] = $userCashWithdrawal->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewUserCashWithdrawals'));
/**
 * PUT usersUserIdUserCashWithdrawalsUserCashWithdrawalsIdPut
 * Summary: Update  user cash withdrawals.
 * Notes: Update user cash withdrawals.
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/users/{userId}/user_cash_withdrawals/{userCashWithdrawalsId}', function ($request, $response, $args)
{
    $body = $request->getParsedBody();
    $result = array();
    $userCashWithdrawal = Models\UserCashWithdrawals::with('user')->where('id', $request->getAttribute('userCashWithdrawalsId'))->first();
    if (empty($validationErrorFields)) {
        if (!empty($userCashWithdrawal)) {
            foreach ($body as $key => $arg) {
                $userCashWithdrawal->{$key} = $arg;
            }
            $userCashWithdrawal->save();
            $emailFindReplace = array(
                '##USERNAME##' => $userCashWithdrawal['user']['username']
            );
            sendMail('adminpaidyourwithdrawalrequest', $emailFindReplace, $userCashWithdrawal['user']['email']);
            $result['data'] = $userCashWithdrawal->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'User cash withdrawals could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'User cash withdrawals could not be updated. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canUpdateUserCashWithdrawals'));
/**
 * GET user cash withdrawals GET.
 * Summary: Filter  user cash withdrawals.
 * Notes: Filter user cash withdrawals.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/user_cash_withdrawals', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $userCashWithdrawals = Models\UserCashWithdrawals::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $userCashWithdrawals['data'];
        unset($userCashWithdrawals['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $userCashWithdrawals
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListUserCashWithdrawals'));
/**
 * GET paymentGatewayGet
 * Summary: Filter  payment gateway
 * Notes: Filter payment gateway.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/payment_gateways', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $paymentGateways = Models\PaymentGateway::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $paymentGateways['data'];
        unset($paymentGateways['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $paymentGateways
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListPaymentGateway'));
/**
 * PUT paymentGatewayspaymentGatewayIdPut
 * Summary: Update Payment gateway by its id
 * Notes: Update Payment gateway.
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/payment_gateways/{paymentGatewayId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $paymentGateway = Models\PaymentGateway::find($request->getAttribute('paymentGatewayId'));
    foreach ($args as $key => $arg) {
        $paymentGateway->{$key} = $arg;
    }
    try {
        $paymentGateway->save();
        $result['data'] = $paymentGateway->toArray();
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Payment gateway could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdatePaymentGateway'));
/**
 * POST countriesPost
 * Summary: Create New countries
 * Notes: Create countries.
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/countries', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $country = new Models\Country;
    $validationErrorFields = $country->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $country->{$key} = $arg;
        }
        $country->slug = Inflector::slug(strtolower($country->name) , '-');
        try {
            $country->save();
            $result = $country->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Country could not be added. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Country could not be added. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canCreateCountry'));
/**
 * DELETE countrycountryIdDelete
 * Summary: DELETE country by admin
 * Notes: DELETE country.
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/countries/{countryId}', function ($request, $response, $args)
{
    $result = array();
    $country = Models\Country::find($request->getAttribute('countryId'));
    try {
        if (!empty($country)) {
            $country->delete();
            $result = array(
                'status' => 'success',
            );
            return renderWithJson($result);
        } else {
            return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Country could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteCountry'));
/**
 * GET countriesGet
 * Summary: Filter  countries
 * Notes: Filter countries.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/countries', function ($request, $response, $args) use ($app)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        if (!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
            $result['data'] = Models\Country::get();
        } else {
            $countries = Models\Country::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
            $data = $countries['data'];
            unset($countries['data']);
            $result = array(
                'data' => $data,
                '_metadata' => $countries
            );
        }
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * PUT countriesCountryIdPut
 * Summary: Update countries by admin
 * Notes: Update countries.
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/countries/{countryId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $country = Models\Country::find($request->getAttribute('countryId'));
    $validationErrorFields = $country->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $country->{$key} = $arg;
        }
        $country->slug = Inflector::slug(strtolower($country->name) , '-');
        try {
            $country->save();
            $result['data'] = $country->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Country could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Country could not be updated. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canUpdateCountry'));
/**
 * GET countriescountryIdGet
 * Summary: Get countries
 * Notes: Get countries.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/countries/{countryId}', function ($request, $response, $args)
{
    $result = array();
    $country = Models\Country::find($request->getAttribute('countryId'));
    if (!empty($country)) {
        $result['data'] = $country->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewCountry'));
/**
 * PUT SettingsSettingIdPut
 * Summary: Update setting by admin
 * Notes: Update setting by admin
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/settings/{settingId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $setting = Models\Setting::find($request->getAttribute('settingId'));
    foreach ($args as $key => $arg) {
        $setting->{$key} = $arg;
    }
    try {
        $setting->save();
        $result['data'] = $setting->toArray();
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Setting could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateSetting'));
/**
 * GET SettingGet .
 * Summary: Get settings.
 * Notes: GEt settings.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/settings', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $setting = new Models\Setting;
        if (!empty($queryParams['type']) && $queryParams['type'] == 'all') {
            $result['data'] = $setting->get();
        } else {
            $settings = $setting->Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
            $data = $settings['data'];
            unset($settings['data']);
            $result = array(
                'data' => $data,
                '_metadata' => $settings
            );
        }
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * GET settingssettingIdGet
 * Summary: GET particular Setting.
 * Notes: Get setting.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/settings/{settingId}', function ($request, $response, $args)
{
    $result = array();
    $setting = Models\Setting::find($request->getAttribute('settingId'));
    if (!empty($setting)) {
        $result['data'] = $setting->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewSetting'));
/**
 * GET SettingcategoriesGet
 * Summary: Filter  Setting categories
 * Notes: Filter Setting categories.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/setting_categories', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $settingCategories = Models\SettingCategory::Filter($queryParams)->where('parent_id', '!=', 0)->paginate(PAGE_LIMIT)->toArray();
        $data = $settingCategories['data'];
        unset($settingCategories['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $settingCategories
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListSettingCategory'));
/**
 * GET SettingcategoriesSettingCategoryIdGet
 * Summary: Get setting categories.
 * Notes: GEt setting categories.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/setting_categories/{settingCategoryId}', function ($request, $response, $args)
{
    $result = array();
    $settingCategory = Models\SettingCategory::find($request->getAttribute('settingCategoryId'));
    if (!empty($settingCategory)) {
        $result['data'] = $settingCategory->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canListSettingCategory'));
/**
 * POST UserPost
 * Summary: Create New user by admin
 * Notes: Create New user by admin
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/users', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $user = new Models\User;
    $validationErrorFields = $user->validate($args);
    if (checkAlreadyUsernameExists($args['username'])) {
        $validationErrorFields['username'] = 'Already this username exists. Try another username';
    } elseif (checkAlreadyEmailExists($args['email'])) {
        $validationErrorFields['email'] = 'Already this email exists. Try another email address';
    } elseif (checkAlreadyMobileExists($args['mobile'])) {
        $validationErrorFields['mobile'] = 'Already this mobile number exists. Try another email address';
    }
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            if ($key == 'password') {
                $user->{$key} = getCryptHash($arg);
            } else {
                $user->{$key} = $arg;
            }
        }
        $user->role_id = \Constants\ConstUserTypes::EventOrganizer;
        $user->country_id = findCountryIdFromIso2($args['country_iso2']);
        $user->state_id = findOrSaveAndGetStateId($args['state_name'], $user->country_id);
        $user->city_id = findOrSaveAndGetCityId($args['city_name'], $user->country_id, $user->state_id);
        unset($user->country_iso2);
        unset($user->state_name);
        unset($user->city_name);
        try {
            if (USER_IS_ADMIN_ACTIVATE_AFTER_REGISTER == 0 || USER_IS_AUTO_LOGIN_AFTER_REGISTER == 1) {
                $user->is_active = 1;
                $user->is_email_confirmed = 1;
            }
            $user->save();
            if (USER_IS_ADMIN_ACTIVATE_AFTER_REGISTER == 0 || USER_IS_AUTO_LOGIN_AFTER_REGISTER == 1) {
                $emailFindReplace = array(
                    '##USERNAME##' => $user->username,
                    '##SUPPORT_EMAIL##' => SUPPORT_EMAIL
                );
                if (USER_IS_WELCOME_MAIL_AFTER_REGISTER == 1) {
                    sendMail('welcomemail', $emailFindReplace, $user->email);
                }
            } elseif (USER_IS_EMAIL_VERIFICATION_FOR_REGISTER == 1) {
                $emailFindReplace = array(
                    '##USERNAME##' => $user->username,
                    '##ACTIVATION_URL##' => 'http://' . $_SERVER['HTTP_HOST'] . '/#/users/activation/' . $user->id . '/' . md5($user->username)
                );
                sendMail('activationrequest', $emailFindReplace, $user->email);
            } else {
            }
            $result = $user->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'User could not be added. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'User could not be added. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canCreateUser'));
/**
 * DELETE UseruserId Delete
 * Summary: DELETE user by admin
 * Notes: DELETE user by admin
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/users/{userId}', function ($request, $response, $args)
{
    $result = array();
    $user = Models\User::find($request->getAttribute('userId'));
    $data = $user;
    if (!empty($user)) {
        try {
            $user->delete();
            $emailFindReplace = array(
                '##USERNAME##' => $data['username']
            );
            sendMail('adminuserdelete', $emailFindReplace, $data['email']);
            $result = array(
                'status' => 'success',
            );
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'User could not be deleted. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Invalid User details.', '', 1);
    }
})->add(new ACL('canDeleteUser'));
/**
 * GET UsersGet
 * Summary: Filter  users
 * Notes: Filter users.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/users', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $users = Models\User::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $users['data'];
        unset($users['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $users
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListUser'));
/**
 * PUT UsersuserIdPut
 * Summary: Update user
 * Notes: Update user
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/users/{userId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $user = Models\User::find($request->getAttribute('userId'));
    if (!empty($user)) {
        foreach ($args as $key => $arg) {
            $user->{$key} = $arg;
        }
        if(!empty($args['country_iso2']) && !empty($args['state_name']) && !empty($args['city_name'])) {
            $user->country_id = findCountryIdFromIso2($args['country_iso2']);
            $user->state_id = findOrSaveAndGetStateId($args['state_name'], $user->country_id);
            $user->city_id = findOrSaveAndGetCityId($args['city_name'], $user->country_id, $user->state_id);
            unset($user->country_iso2);
            unset($user->state_name);
            unset($user->city_name);
        }
        try {
            $user->save();
            $result['data'] = $user->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'User could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Invalid user Details, try again.', '', 1);
    }
})->add(new ACL('canUpdateUser'));
/**
 * GET UseruserIdGet
 * Summary: Get particular user details
 * Notes: Get particular user details
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/users/{userId}', function ($request, $response, $args)
{
    $result = array();
    $user = Models\User::with('city', 'state', 'country')->where('id', $request->getAttribute('userId'))->first();
    if (!empty($user)) {
        $result['data'] = $user;
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
/**
 * PUT UsersuserIdChangePasswordPut .
 * Summary: update change password
 * Notes: update change password
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/users/{userId}/change_password', function ($request, $response, $args)
{
    global $authUser;
    $result = array();
    $args = $request->getParsedBody();
    $user = Models\User::find($request->getAttribute('userId'));
    $validationErrorFields = $user->validate($args);
    $password = crypt($args['password'], $user['password']);
    if (empty($validationErrorFields)) {
        if ($password == $user['password']) {
            $change_password = $args['new_password'];
            $user->password = getCryptHash($change_password);
            try {
                $user->save();
                $emailFindReplace = array(
                    '##PASSWORD##' => $args['new_password'],
                    '##USERNAME##' => $user['username']
                );
                if ($authUser['role_id'] == \Constants\ConstUserTypes::Admin) {
                    sendMail('adminchangepassword', $emailFindReplace, $user->email);
                } else {
                    sendMail('changepassword', $emailFindReplace, $user['email']);
                }
                $result['data'] = $user->toArray();
                return renderWithJson($result);
            }
            catch(Exception $e) {
                return renderWithJson($result, 'User Password could not be updated. Please, try again', '', 1);
            }
        } else {
            return renderWithJson($result, 'Password is invalid . Please, try again', '', 1);
        }
    } else {
        return renderWithJson($result, 'User Password could not be updated. Please, try again', $validationErrorFields, 1);
    }
})->add(new ACL('canUpdateUser'));
/**
 * POST StatesPost
 * Summary: Create New states
 * Notes: Create states.
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/states', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $state = new Models\State;
    $validationErrorFields = $state->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $state->{$key} = $arg;
        }
        $state->slug = Inflector::slug(strtolower($state->name) , '-');
        try {
            $state->save();
            $result = $state->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'State could not be added. Please, try again', '', 1);
        }
    } else {
        return renderWithJson($result, 'State could not be added. Please, try again', $validationErrorFields, 1);
    }
});
/**
 * DELETE StatesStateIdDelete
 * Summary: DELETE states by admin
 * Notes: DELETE states by admin
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/states/{stateId}', function ($request, $response, $args)
{
    $result = array();
    $state = Models\State::find($request->getAttribute('stateId'));
    try {
        $state->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'State could not be added. Please, try again', '', 1);
    }
})->add(new ACL('canDeleteState'));
/**
 * GET StatesGet
 * Summary: Filter  states
 * Notes: Filter states.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/states', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $states = Models\State::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $states['data'];
        unset($states['data']);
        if ($queryParams[filter] == 'all') {
            $data = Models\State::all()->toArray();
        }
        $result = array(
            'data' => $data,
            '_metadata' => $states
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * PUT StatesStateIdPut
 * Summary: Update states by admin
 * Notes: Update states.
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/states/{stateId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $state = Models\State::find($request->getAttribute('stateId'));
    $validationErrorFields = $state->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $state->{$key} = $arg;
        }
        $state->slug = Inflector::slug(strtolower($state->name) , '-');
        try {
            $state->save();
            $result['data'] = $state->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'State could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'State could not be updated. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canUpdateState'));
/**
 * GET StatesstateIdGet
 * Summary: Get  particular state
 * Notes: Get  particular state
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/states/{stateId}', function ($request, $response, $args)
{
    $result = array();
    $state = Models\State::find($request->getAttribute('stateId'));
    if (!empty($state)) {
        $result['data'] = $state->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewState'));
/**
 * POST usersForgotPasswordPost
 * Summary: User forgot password
 * Notes: User forgot password
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/users/forgot_password', function ($request, $response, $args)
{
    $result = array();
    $args = $request->getParsedBody();
    $user = Models\User::where('email', $args['email'])->first();
    if (!empty($user)) {
        $validationErrorFields = $user->validate($args);
        if (empty($validationErrorFields) && !empty($user)) {
            try {
                $emailFindReplace = array(
                    '##USERNAME##' => $user['username'],
                    '##RESET_URL##' => 'http://' . $_SERVER['HTTP_HOST'] . '/#/users/reset_password/' . md5($user->username)
                );
                sendMail('forgotpassword', $emailFindReplace, $user['email']);
                return renderWithJson($result, 'An email has been sent with your new password', '', 0);
            }
            catch(Exception $e) {
                return renderWithJson($result, 'Email Not found', '', 1);
            }
        } else {
            return renderWithJson($result, 'Process could not be found', $validationErrorFields, 1);
        }
    } else {
        return renderWithJson($result, 'No data found', '', 1);
    }
});
/**
 * PUT usersResetPasswordHashPut
 * Summary: User Reset Password
 * Notes: Send activation hash code to user for reset password. \n
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/users/reset_password/{hash}', function ($request, $response, $args)
{
    $result = array();
    $args = $request->getParsedBody();
    $user = Models\User::where('email', $args['email'])->first();
    if (!empty($user)) {
        if (md5($user['username']) == $args['hash']) {
            $user->password = getCryptHash($args['password']);
            $user->save();
            $result['data'] = $user->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Invalid user details.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Invalid user details.', '', 1);
    }
});
/**
 * POST usersLoginPost
 * Summary: User login
 * Notes: User login information post
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/users/login', function ($request, $response, $args)
{
    $body = $request->getParsedBody();
    $result = array();
    $user = new Models\User;
    if (USER_USING_TO_LOGIN == 'username') {
        $log_user = $user->where('username', $body['username'])->where('is_active', 1)->where('is_email_confirmed', 1)->first();
    } else {
        $log_user = $user->where('email', $body['email'])->where('is_active', 1)->where('is_email_confirmed', 1)->first();
    }
    $password = crypt($body['password'], $log_user['password']);
    $validationErrorFields = $user->validate($body);
    if (empty($validationErrorFields) && !empty($log_user) && ($password == $log_user['password'])) {
        $scopes = '';
        if (!empty($log_user['role_id']) && !empty($log_user['scopes_' . $log_user['role_id']])) {
            $scopes = implode(' ', $log_user['scopes_' . $log_user['role_id']]);
        }
        $post_val = array(
            'grant_type' => 'password',
            'username' => $log_user['username'],
            'password' => $password,
            'client_id' => OAUTH_CLIENT_ID,
            'client_secret' => OAUTH_CLIENT_SECRET,
            'scope' => $scopes
        );
        $response = getToken($post_val);
        if (!empty($response['refresh_token'])) {
            $result = $response + $log_user->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Your login credentials are invalid.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Your login credentials are invalid.', $validationErrorFields, 1);
    }
});
/**
 * POST usersRegisterPost
 * Summary: new user
 * Notes: Post new user.
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/users/register', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $user = new Models\User;
    $validationErrorFields = $user->validate($args);
    if (checkAlreadyUsernameExists($args['username'])) {
        $validationErrorFields['username'] = 'Already this username exists.';
    } elseif (checkAlreadyEmailExists($args['email'])) {
        $validationErrorFields['email'] = 'Already this email exists.';
    } elseif (checkAlreadyMobileExists($args['mobile'])) {
        $validationErrorFields['mobile'] = 'Already this mobile number exists';
    }
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            if ($key == 'password') {
                $user->{$key} = getCryptHash($arg);
            } else {
                $user->{$key} = $arg;
            }
        }
        try {
            if (USER_IS_ADMIN_ACTIVATE_AFTER_REGISTER == 0 || USER_IS_AUTO_LOGIN_AFTER_REGISTER == 1) {
                $user->is_active = 1;
                $user->is_email_confirmed = 1;
            }
            $user->gender_id = 2;
            if ($user->title == 'Mr') {
                $user->gender_id = 1;
            }
            $user->save();
            if (USER_IS_ADMIN_ACTIVATE_AFTER_REGISTER == 0 || USER_IS_AUTO_LOGIN_AFTER_REGISTER == 1) {
                $emailFindReplace = array(
                    '##USERNAME##' => $user->username,
                    '##SUPPORT_EMAIL##' => SUPPORT_EMAIL
                );
                // send to admin mail if USER_IS_ADMIN_MAIL_AFTER_REGISTER is true
                if (USER_IS_ADMIN_MAIL_AFTER_REGISTER == 1) {
                    $emailFindReplace = array(
                        '##USERNAME##' => $user->username,
                        '##USEREMAIL##' => $user->email
                    );
                    sendMail('newuserjoin', $emailFindReplace, SITE_CONTACT_EMAIL);
                }
                // send welcome mail to user if USER_IS_WELCOME_MAIL_AFTER_REGISTER is true
                if (USER_IS_WELCOME_MAIL_AFTER_REGISTER == 1) {
                    sendMail('welcomemail', $emailFindReplace, $user->email);
                }
            } elseif (USER_IS_EMAIL_VERIFICATION_FOR_REGISTER == 1) {
                $emailFindReplace = array(
                    '##USERNAME##' => $user->username,
                    '##ACTIVATION_URL##' => 'http://' . $_SERVER['HTTP_HOST'] . '/#/users/activation/' . $user->id . '/' . md5($user->username)
                );
                sendMail('activationrequest', $emailFindReplace, $user->email);
            }
            if (USER_IS_AUTO_LOGIN_AFTER_REGISTER == 1) {
                $scopes = '';
                if (isset($user->role_id) && $user->role_id == \Constants\ConstUserTypes::User) {
                    $scopes = implode(' ', $user['user_scopes']);
                } else {
                    $scopes = '';
                }
                $post_val = array(
                    'grant_type' => 'password',
                    'username' => $user->username,
                    'password' => $user->password,
                    'client_id' => OAUTH_CLIENT_ID,
                    'client_secret' => OAUTH_CLIENT_SECRET,
                    'scope' => $scopes
                );
                $response = getToken($post_val);
                $result = $response + $user->toArray();
            } else {
                $result = $user->toArray();
            }
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'User could not be added. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'User could not be added. Please, try again.', $validationErrorFields, 1);
    }
});
/**
 * PUT usersUserIdActivationHashPut
 * Summary: User activation
 * Notes: Send activation hash code to user for activation. \n
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/users/{userId}/activation/{hash}', function ($request, $response, $args)
{
    $result = array();
    $user = Models\User::where('id', $args['userId'])->first();
    if (!empty($user)) {
        if (md5($user['username']) == $args['hash']) {
            $user->is_active = 1;
            $user->is_agree_terms_conditions = 1;
            $user->is_email_confirmed = 1;
            $user->save();
            $result['data'] = $user->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Invalid user deatails.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Invalid user deatails.', '', 1);
    }
});
/**
 * POST moneyTransferAccountPost
 * Summary: Create New money transfer account
 * Notes: Create money transfer account.
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/money_transfer_accounts', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $moneyTransferAccount = new Models\MoneyTransferAccount;
    $validationErrorFields = $moneyTransferAccount->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $moneyTransferAccount->{$key} = $arg;
        }
        try {
            $moneyTransferAccount->save();
            $result = $moneyTransferAccount->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Account could not be added. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Account could not be added. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('canCreateMoneyTransferAccount'));
/**
 * PUT moneyTransferAccountMoneyTransferAccountIdPut
 * Summary: Update money transfer account by its id
 * Notes: Update money transfer account.
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/money_transfer_accounts/{MoneyTransferAccountId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $moneyTransferAccount = Models\MoneyTransferAccount::find($request->getAttribute('MoneyTransferAccountId'));
    $validationErrorFields = $moneyTransferAccount->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $moneyTransferAccount->{$key} = $arg;
        }
        try {
            $moneyTransferAccount->save();
            $result['data'] = $moneyTransferAccount->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Account could not be updated. Please, try again', '', 1);
        }
    } else {
        return renderWithJson($result, 'Account could not be updated. Please, try again', $validationErrorFields, 1);
    }
})->add(new ACL('canUpdateMoneyTransferAccount'));
/**
 * GET MoneyTransferAccountsGet
 * Summary: Get money transfer accounts lists
 * Notes: Get money transfer accounts lists
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/money_transfer_accounts', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $moneyTransferAccounts = Models\MoneyTransferAccount::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $moneyTransferAccounts['data'];
        unset($moneyTransferAccounts['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $moneyTransferAccounts
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListMoneyTransferAccount'));
/**
 * GET MoneyTransferAccountsMoneyTransferAccountIdGet
 * Summary: Get particular money transfer accounts
 * Notes: Get particular money transfer accounts
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/money_transfer_accounts/{moneyTransferAccountId}', function ($request, $response, $args)
{
    $result = array();
    $moneyTransferAccount = Models\MoneyTransferAccount::find($request->getAttribute('moneyTransferAccountId'));
    if (!empty($moneyTransferAccount)) {
        $result['data'] = $moneyTransferAccount->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewMoneyTransferAccount'));
/**
 * DELETE MoneyTransferAccountsMoneyTransferAccountIdDelete
 * Summary: Delete money transfer account
 * Notes: Delete money transfer account
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/money_transfer_accounts/{moneyTransferAccountId}', function ($request, $response, $args)
{
    $result = array();
    $moneyTransferAccount = Models\MoneyTransferAccount::where('id', $request->getAttribute('moneyTransferAccountId'))->first();
    try {
        $moneyTransferAccount->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Money transfer account could not be added. Please, try again', '', 1);
    }
})->add(new ACL('canDeleteMoneyTransferAccount'));
/**
 * GET ProvidersGet
 * Summary: all providers lists
 * Notes: all providers lists
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/providers', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $providers = Models\Provider::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $providers['data'];
        unset($providers['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $providers
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * PUT ProvidersProviderIdPut
 * Summary: Update provider details
 * Notes: Update provider details.
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/providers/{providerId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $provider = Models\Provider::find($request->getAttribute('providerId'));
    $validationErrorFields = $provider->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $provider->{$key} = $arg;
        }
        try {
            $provider->save();
            $result['data'] = $provider->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Provider could not be updated. Please, try again', '', 1);
        }
    } else {
        return renderWithJson($result, 'Provider could not be updated. Please, try again', $validationErrorFields, 1);
    }
})->add(new ACL('canUpdateProvider'));
/**
 * GET  ProvidersProviderIdGet
 * Summary: Get  particular provider details
 * Notes: GEt particular provider details.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/providers/{providerId}', function ($request, $response, $args)
{
    $result = array();
    $provider = Models\Provider::find($request->getAttribute('providerId'));
    if (!empty($provider)) {
        $result['data'] = $provider->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
/**
 * GET TransactionGet
 * Summary: Get all transactions list.
 * Notes: Get all transactions list.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/transactions', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $transactions = Models\Transaction::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $transactions['data'];
        unset($transactions['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $transactions
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListAllTransactions'));
/**
 * GET UsersUserIdTransactionsGet
 * Summary: Get user transactions list.
 * Notes: Get user transactions list.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/users/{userId}/transactions', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $transactions = Models\Transaction::where('user_id', $request->getAttribute('userId'))->Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $transactions['data'];
        unset($transactions['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $transactions
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListUserTransactions'));
/**
 * GET EmailTemplateGet
 * Summary: Get email templates lists
 * Notes: Get email templates lists
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/email_templates', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $emailTemplates = Models\EmailTemplate::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $emailTemplates['data'];
        unset($emailTemplates['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $emailTemplates
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListEmailTemplate'));
/**
 * GET EmailTemplateemailTemplateIdGet
 * Summary: Get paticular email templates
 * Notes: Get paticular email templates
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/email_templates/{emailTemplateId}', function ($request, $response, $args)
{
    $result = array();
    $emailTemplate = Models\EmailTemplate::find($request->getAttribute('emailTemplateId'));
    if (!empty($emailTemplate)) {
        $result['data'] = $emailTemplate->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewEmailTemplate'));
/**
 * PUT EmailTemplateemailTemplateIdPut
 * Summary: Put paticular email templates
 * Notes: Put paticular email templates
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/email_templates/{emailTemplateId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $emailTemplate = Models\EmailTemplate::find($request->getAttribute('emailTemplateId'));
    $validationErrorFields = $emailTemplate->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $emailTemplate->{$key} = $arg;
        }
        try {
            $emailTemplate->save();
            $result['data'] = $emailTemplate->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Email template could not be updated. Please, try again', '', 1);
        }
    } else {
        return renderWithJson($result, 'Email template could not be updated. Please, try again', $validationErrorFields, 1);
    }
})->add(new ACL('canUpdateEmailTemplate'));
/**
 * GET RoleGet
 * Summary: Get roles lists
 * Notes: Get roles lists
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/roles', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $roles = Models\Role::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $roles['data'];
        unset($roles['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $roles
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * GET RolesIdGet
 * Summary: Get paticular email templates
 * Notes: Get paticular email templates
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/roles/{roleId}', function ($request, $response, $args)
{
    $result = array();
    $role = Models\Role::find($request->getAttribute('roleId'));
    if (!empty($role)) {
        $result = $role->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
/**
 * GET StatsGet
 * Summary: Get site stats lists
 * Notes: Get site stats lists
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/stats', function ($request, $response, $args)
{
    global $authUser;
    $result = array();
    if($authUser->role_id == \Constants\ConstUserTypes::Admin) {
        $result['customers'] = Models\User::where('is_active', 1)->where('is_email_confirmed', 1)->count();
        $result['total_events'] = Models\Event::count();
        $result['active_events'] = Models\Event::where('event_status_id', \Constants\EventStatus::Open)->count();
        $result['past_events'] = Models\Event::where('event_status_id', \Constants\EventStatus::Closed)->count();
        $result['venue'] = Models\Venue::where('is_active', 1)->count();
        $result['total_visitors'] = Models\User::count();
        $result['total_sales'] = Models\Order::sum('quantity');
        $total_revenue = Models\Order::sum('total_amount');
        $result['total_revenue'] = round($total_revenue);
    } else {
        $result['total_events'] = Models\Event::where('user_id', $authUser->id)->count();
        $result['active_events'] = Models\Event::where('user_id', $authUser->id)->where('event_status_id', \Constants\EventStatus::Open)->count();
        $result['past_events'] = Models\Event::where('user_id', $authUser->id)->where('event_status_id', \Constants\EventStatus::Closed)->count();
        $result['total_visitors'] = Models\Event::where('user_id', $authUser->id)->sum('view_count');
        $user_events = Models\Event::where('user_id', $authUser->id)->select('id')->get()->toArray();
        if(!empty($user_events)){
            foreach($user_events as $user_event) {
            $event_id[] = $user_event['id'];
        }
       $result['total_sales'] = Models\Order::whereIn('event_id', $event_id)->sum('quantity');
       $total_revenue = Models\Order::whereIn('event_id', $event_id)->sum('total_amount');
       $result['total_revenue'] = round($total_revenue);
        } 
        else{
            $result['total_sales'] = 0;
            $result['total_revenue'] = 0;
        }       
    }
    return renderWithJson($result);
})->add(new ACL('canViewStats'));

$app->GET('/api/v1/sales_reports', function ($request, $response, $args)
{
    global $authUser, $capsule;
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        if($authUser->role_id != \Constants\ConstUserTypes::Admin) {
            $events = Models\Event::where('user_id', $authUser->id)->get()->toArray();
            foreach($events as $event) {
                $event_id[] = $event['id'];
            }
            if(!empty($event_id)) {
                $where_query =  !empty($queryParams['event_id']) ? 'WHERE event_id = ' . $queryParams['event_id'] : 'WHERE "event_id" IN (' . implode(',', $event_id) . ')';
                if(!empty($queryParams['event_schedule_id'])) {
                    $where_query .= ' AND event_schedule_id = ' . $queryParams['event_schedule_id'];
                }
                if(!empty($queryParams['price_type_id'])) {
                    $where_query .= ' AND price_type_id = ' . $queryParams['price_type_id'];
                }
                if(!empty($queryParams['sales_channel'])) {
                    if($queryParams['sales_channel'] == 'mobile') {
                        $where_query .= ' AND is_booked_via_mobile = 1';
                    } else if($queryParams['sales_channel'] == 'site') {
                        $where_query .= ' AND is_booked_via_mobile = 0';
                    }
                }
                if(!empty($queryParams['start_date'])) {
                    $where_query .= ' AND created_at >= \'' . $queryParams['start_date'] .' 00:00:00\' ';
                }
                if(!empty($queryParams['end_date'])) {
                    $where_query .= ' AND created_at <= \'' . $queryParams['start_date'] .' 23:59:59\' ';
                }
                if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
                    $salesReport['data'] = $capsule::select('SELECT created_at::DATE, SUM("quantity") AS quantity, SUM("total_amount") AS total_amount FROM "orders" ' . $where_query . 'GROUP BY created_at::DATE ORDER BY created_at DESC');
                    $data['sales_report'] = objectToArray($salesReport['data']);
                    if(!empty($queryParams['event_id'])) {
                        $data['event_details'] = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
                        $data['event_details']['total_sale'] = Models\Order::Filter($queryParams)->sum('quantity'); 
                        $data['event_details']['total_revenue'] = Models\Order::Filter($queryParams)->sum('total_amount');
                    }
                } else {
                    $count = !empty($queryParams['limit']) ? $queryParams['limit'] : PAGE_LIMIT;
                    $page = !empty($queryParams['page']) ? $queryParams['page'] : 1;
                    $offset = ($page-1) * $count;
                    if(!empty($queryParams['event_id'])) {
                        $event = Models\Event::find($queryParams['event_id']);
                        if(empty($event) || $event->user_id != $authUser->id) {
                            return renderWithJson($results, $message = 'Invalid user', $fields = '', $isError = 1);    
                        }
                    }
                    $salesReport['data'] = $capsule::select('SELECT created_at::DATE, SUM("quantity") AS quantity, SUM("total_amount") AS total_amount FROM "orders" ' . $where_query . ' GROUP BY created_at::DATE ORDER BY created_at DESC LIMIT ? OFFSET ?', array($count, $offset));
                    $data['sales_report'] = objectToArray($salesReport['data']);
                    if(!empty($queryParams['event_id'])) {
                        $data['event_details'] = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
                        $data['event_details']['total_sale'] = Models\Order::Filter($queryParams)->sum('quantity'); 
                        $data['event_details']['total_revenue'] = Models\Order::Filter($queryParams)->sum('total_amount');
                    }
                }
            } else {
                return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
            }
        } else {
            $where_query =  !empty($queryParams['event_id']) ? 'WHERE event_id = ' . $queryParams['event_id'] : '';
            if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
                $salesReport['data'] = $capsule::select('SELECT created_at::DATE, SUM("quantity") AS quantity, SUM("total_amount") AS total_amount FROM "orders" ' . $where_query . ' GROUP BY created_at::DATE ORDER BY created_at DESC');
                $data['sales_report'] = objectToArray($salesReport['data']);
                if(!empty($queryParams['event_id'])) {
                    $data['event_details'] = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
                    $data['event_details']['total_sale'] = Models\Order::Filter($queryParams)->sum('quantity'); 
                    $data['event_details']['total_revenue'] = Models\Order::Filter($queryParams)->sum('total_amount');
                }    
            } else {
                $count = !empty($queryParams['limit']) ? $queryParams['limit'] : PAGE_LIMIT;
                $page = !empty($queryParams['page']) ? $queryParams['page'] : 1;
                $offset = ($page-1) * $count;
                $total_records = $capsule::select('SELECT created_at::DATE, SUM("quantity") AS quantity, SUM("total_amount") AS total_amount FROM "orders" ' . $where_query . ' GROUP BY created_at::DATE ORDER BY created_at DESC');
                $salesReport['data'] = $capsule::select('SELECT created_at::DATE, SUM("quantity") AS quantity, SUM("total_amount") AS total_amount FROM "orders" ' . $where_query . ' GROUP BY created_at::DATE ORDER BY created_at DESC LIMIT ? OFFSET ?', array($count, $offset));
                $_metadata = setMetaData($count, $page, $offset, $total_records);
                $data['sales_report'] = objectToArray($salesReport['data']);
                if(!empty($queryParams['event_id'])) {
                    $data['event_details'] = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
                    $data['event_details']['total_sale'] = Models\Order::Filter($queryParams)->sum('quantity'); 
                    $data['event_details']['total_revenue'] = Models\Order::Filter($queryParams)->sum('total_amount');
                }
            }
        }
        $total_details['total_quantity'] = Models\Order::Filter($queryParams)->sum('quantity'); 
        $total_details['total_revenue'] = Models\Order::Filter($queryParams)->sum('total_amount');
        unset($salesReport['data']);
        $results = array(
            'data' => $data,
            'total_details' => $total_details,           
            '_metadata' => $_metadata
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListSalesReport'));

$app->GET('/api/v1/sales_report_details', function ($request, $response, $args)
{
    global $authUser, $capsule;
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        if($authUser->role_id != \Constants\ConstUserTypes::Admin) {
            if(!empty($queryParams['event_id'])) {
                $event = Models\Event::find($queryParams['event_id']);
                if($event->user_id != $authUser->id) {
                    return renderWithJson($results, $message = 'Invalid user', $fields = '', $isError = 1);    
                }
            } 
        }
        $_metadata = array();
        if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
            $salesReportDetail['data']['price_type'] = Models\OrderItem::with('price_type')->select('price_type_id')->groupBy('price_type_id')->Filter($queryParams)->get()->toArray();
            $i = 0;
            foreach($salesReportDetail['data']['price_type'] as $price_type) {
                $order_ids_by_type = Models\OrderItem::with('price_type')->Filter($queryParams)->where('price_type_id', $price_type['price_type_id'])->get()->toArray();
                if (!empty($order_ids_by_type)) {
                    $ids_by_type = array();
                    foreach($order_ids_by_type as $order_id_by_type) {
                        $ids_by_type[] = $order_id_by_type['order_id'];
                    }
                    $ids_by_type = array_unique($ids_by_type);
                    $ticket_sold_by_type = $capsule::select('SELECT SUM("quantity") as quantity, SUM("total_amount") as revenue FROM "orders" WHERE id IN (' . implode(',', $ids_by_type) . ')');
                    $salesReportDetail['data']['price_type'][$i]['sold_quantity'] = $ticket_sold_by_type[0]->quantity;
                    $salesReportDetail['data']['price_type'][$i]['revenue'] = $ticket_sold_by_type[0]->revenue;
                } else {
                    $salesReportDetail['data']['price_type'][$i]['sold_quantity'] = 0;
                    $salesReportDetail['data']['price_type'][$i]['revenue'] = 0;
                }
                $i++;
            }
            if(!empty($queryParams['event_id'])) {
                $salesReportDetail['data']['event_details'] = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
                $salesReportDetail['data']['event_details']['total_sale'] = Models\Order::where('event_id', $queryParams['event_id'])->sum('quantity');
                $salesReportDetail['data']['event_details']['total_revenue'] = Models\Order::where('event_id', $queryParams['event_id'])->sum('total_amount');
            }  
        } else {
            $count = !empty($queryParams['limit']) ? $queryParams['limit'] : PAGE_LIMIT;
            $page = !empty($queryParams['page']) ? $queryParams['page'] : 1;
            $offset = ($page-1) * $count;
            $total_records = Models\OrderItem::with('price_type')->select('price_type_id')->groupBy('price_type_id')->Filter($queryParams)->get()->toArray();
            $salesReportDetail['data']['price_type'] = $capsule::select('SELECT order_items.price_type_id, LOWER(price_types.name) as price_type_name FROM "order_items", "price_types" WHERE price_types.id = order_items.price_type_id GROUP BY order_items.price_type_id, price_types.name LIMIT ? OFFSET ?',  array($count, $offset));
            $_metadata = setMetaData($count, $page, $offset, $total_records);
            $i = 0;
            $salesReportDetail['data']['price_type'] = objectToArray($salesReportDetail['data']['price_type']);
            foreach($salesReportDetail['data']['price_type'] as $price_type) {
                $order_ids_by_type = Models\OrderItem::with('price_type')->Filter($queryParams)->where('price_type_id', $price_type['price_type_id'])->get()->toArray();
                if (!empty($order_ids_by_type)) {
                    $ids_by_type = array();
                    foreach($order_ids_by_type as $order_id_by_type) {
                        $ids_by_type[] = $order_id_by_type['order_id'];
                    }
                    $ids_by_type = array_unique($ids_by_type);
                    $ticket_sold_by_type = $capsule::select('SELECT SUM("quantity") as quantity, SUM("total_amount") as revenue FROM "orders" WHERE id IN (' . implode(',', $ids_by_type) . ')');
                    $salesReportDetail['data']['price_type'][$i]['sold_quantity'] = $ticket_sold_by_type[0]->quantity;
                    $salesReportDetail['data']['price_type'][$i]['revenue'] = $ticket_sold_by_type[0]->revenue;
                } else {
                    $salesReportDetail['data']['price_type'][$i]['sold_quantity'] = 0;
                    $salesReportDetail['data']['price_type'][$i]['revenue'] = 0;
                }
                $i++;
            }
            if(!empty($queryParams['event_id'])) {
                $salesReportDetail['data']['event_details'] = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
                $salesReportDetail['data']['event_details']['total_sale'] = Models\Order::where('event_id', $queryParams['event_id'])->sum('quantity');
                $salesReportDetail['data']['event_details']['total_revenue'] = Models\Order::where('event_id', $queryParams['event_id'])->sum('total_amount');
            }
        }
        $data = $salesReportDetail['data'];
        $total_details['total_quantity'] = 0;
        $total_details['total_revenue'] = 0;
        foreach($salesReportDetail['data']['price_type'] as $key => $value) {
            $total_details['total_quantity'] += $value['sold_quantity'];
            $total_details['total_revenue'] += $value['revenue'];
        }
        unset($salesReportDetail['data']);
        $results = array(
            'data' => $data,
            'total_details' => $total_details,
            '_metadata' => $_metadata
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListSalesReportDetail'));

/**
 * GET capacityReportsGet
 * Summary: Fetch all capacity_reports
 * Notes: Returns all capacity_reports
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/capacity_reports', function($request, $response, $args) {
    global $authUser;
	$queryParams = $request->getQueryParams();
	$results = array();
	try {
	    if (!empty($queryParams['event_id'])) {
            if ($authUser->role_id != \Constants\ConstUserTypes::Admin) {
                $event = Models\Event::find($queryParams['event_id']);
                if($event->user_id != $authUser->id) {
                    return renderWithJson($results, $message = 'Invalid user', $fields = '', $isError = 1);    
                }
            }
            $eventSchedules = Models\OrderItem::with('event_schedule')->select('event_schedule_id')->groupBy('event_schedule_id')->Filter($queryParams)->get()->toArray();
            if (!empty($eventSchedules)) {
                foreach($eventSchedules as $event_schedule) {
                    if (!empty($event_schedule['event_schedule_id'])) {
                        $schedule_ids[] = $event_schedule['event_schedule_id'];
                    }
                }
                if (!empty($schedule_ids)) {
                    $eventZones = Models\OrderItem::with('event_zone')->whereIn('event_schedule_id', $schedule_ids)->select('event_zone_id', 'event_schedule_id')->groupBy('event_zone_id', 'event_schedule_id')->Filter($queryParams)->get()->toArray();
                    foreach($eventZones as $event_zone) {
                        $zone_ids[] = $event_zone['event_zone_id'];
                        $event_zone['ticket_sold'] = Models\OrderItem::with('event_zone')->where('event_schedule_id', $event_zone['event_schedule_id'])->where('event_zone_id', $event_zone['event_zone_id'])->count();
                        $event_zones[$event_zone['event_schedule_id']][] = $event_zone;
                    }
                    if (!empty($zone_ids)) {
                        $priceTypes = Models\OrderItem::with('price_type')->whereIn('event_schedule_id', $schedule_ids)->whereIn('event_zone_id', $zone_ids)->select('price_type_id', 'event_zone_id', 'event_schedule_id')->groupBy('price_type_id', 'event_zone_id', 'event_schedule_id')->Filter($queryParams)->get()->toArray();
                        foreach($priceTypes as $price_type) {
                            $price_ids[] = $price_type['price_type_id'];
                            $price_type['ticket_sold'] = Models\OrderItem::with('price_type')->where('event_schedule_id', $price_type['event_schedule_id'])->where('event_zone_id', $price_type['event_zone_id'])->where('price_type_id', $price_type['price_type_id'])->count();
                            $price_types[$price_type['event_schedule_id']][$price_type['event_zone_id']][] = $price_type;
                        }
                    }
                    foreach($eventSchedules as $eventSchedule) {
                        if (!empty($eventSchedule['event_schedule_id'])) {
                            $eventSchedule['event_zones'] = $event_zones[$eventSchedule['event_schedule_id']];
                            $i = 0;
                            foreach($eventSchedule['event_zones'] as $event_zone) {
                                if (!empty($event_zone['event_zone_id'])) {
                                    $eventSchedule['event_zones'][$i]['price_types'] = $price_types[$eventSchedule['event_schedule_id']][$event_zone['event_zone_id']];
                                }
                                $i++;
                            }
                            $data[] = $eventSchedule;
                        }
                    }
                }
                $event_details = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
                $total_details['total_quantity'] = Models\Order::Filter($queryParams)->sum('quantity'); 
                $total_details['total_revenue'] = Models\Order::Filter($queryParams)->sum('total_amount');
                $results = array(
                    'data' => $data,
                    'event_details' => $event_details, 
                    'total_details' => $total_details
                );
            }
            return renderWithJson($results);
        } else {
            return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);    
        }
	}
	catch(Exception $e) {
		return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
	}
})->add(new ACL('canListCapacityReport'));


/**
 * GET demographicReportsGet
 * Summary: Fetch all demographic_reports
 * Notes: Returns all demographic_reports
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/demographic_reports', function($request, $response, $args) {
    global $authUser;
	$queryParams = $request->getQueryParams();
	$results = array();
	try 
    {
        if($authUser->role_id != \Constants\ConstUserTypes::Admin) {
            if(!empty($queryParams['event_id'])) {
                $event = Models\Event::find($queryParams['event_id']);
                if($event->user_id != $authUser->id) {
                    return renderWithJson($results, $message = 'Invalid user', $fields = '', $isError = 1);    
                } else {
                    $user_ids = Models\Order::select('user_id')->groupBy('user_id')->Filter($queryParams)->get()->toArray();
                    foreach($user_ids as $user_id) {
                        $ids[] = $user_id['user_id'];
                    }
                }
            } else {
                $events = Models\Event::where('user_id', $authUser->id)->get()->toArray();
                foreach($events as $event) {
                    $event_id[] = $event['id'];
                }
                if(!empty($event_id)) {
                    $user_ids = Models\Order::whereIn('event_id', $event_id)->select('user_id')->groupBy('user_id')->Filter($queryParams)->get()->toArray();
                    foreach($user_ids as $user_id) {
                        $ids[] = $user_id['user_id'];
                    }   
                } else {
                    return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
                }                
            }
        } else {
            $user_ids = Models\Order::select('user_id')->groupBy('user_id')->Filter($queryParams)->get()->toArray();
            foreach($user_ids as $user_id) {
                $ids[] = $user_id['user_id'];
            }
        }
        $demographicReports['data']['ages'] = Models\User::whereIn('id', $ids)->selectRaw('extract(year from (age(dob))) as age')->selectRaw('count("id")')->groupBy('age')->get()->toArray();
        $demographicReports['data']['genders'] = Models\User::whereIn('id', $ids)->select('gender_id')->selectRaw('count("id")')->groupBy('gender_id')->get()->toArray();
        $demographicReports['data']['occupations'] = Models\User::with('occupation')->whereIn('id', $ids)->select('occupation_id')->selectRaw('count("id")')->groupBy('occupation_id')->get()->toArray();
        $demographicReports['data']['educations'] = Models\User::with('education')->whereIn('id', $ids)->select('education_id')->selectRaw('count("id")')->groupBy('education_id')->get()->toArray();
        $daily_sales = Models\Order::Filter($queryParams)->selectRaw('extract(dow from "created_at") as day')->selectRaw('sum("quantity")')->groupBy('day')->orderBy('day', 'desc')->get()->toArray();
        if(!empty($daily_sales)) {
            foreach($daily_sales as $daily_sale) {
                if($daily_sale['day'] == 0) { $daily_sale['day'] = 'Sunday';}
                if($daily_sale['day'] == 1) { $daily_sale['day'] = 'Monday';}
                if($daily_sale['day'] == 2) { $daily_sale['day'] = 'Tuesday';}
                if($daily_sale['day'] == 3) { $daily_sale['day'] = 'Wednesday';}
                if($daily_sale['day'] == 4) { $daily_sale['day'] = 'Thursday';}
                if($daily_sale['day'] == 5) { $daily_sale['day'] = 'Friday';}
                if($daily_sale['day'] == 6) { $daily_sale['day'] = 'Saturday';}
                $daily_sale_report[] = $daily_sale;
            }
        }
        $demographicReports['data']['daily_sales'] = $daily_sale_report; 
        $demographicReports['data']['hourly_sales'] = Models\Order::Filter($queryParams)->selectRaw('extract(hour from "created_at") as hour')->selectRaw('sum("quantity")')->groupBy('hour')->orderBy('hour', 'desc')->get()->toArray();
        $data = $demographicReports['data'];
        if(!empty($queryParams['event_id'])) {
            $data['event_details'] = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
        }
        $total_details['total_quantity'] = Models\Order::Filter($queryParams)->sum('quantity'); 
        $total_details['total_revenue'] = Models\Order::Filter($queryParams)->sum('total_amount');
        unset($demographicReports['data']);
        $results = array(
            'data' => $data,
            'total_details' => $total_details,
            '_metadata' => $demographicReports
        );
        return renderWithJson($results);
	}
	catch(Exception $e) 
    {
		return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
	}
})->add(new ACL('canListDemographicReport'));


/**
 * GET financialReportsGet
 * Summary: Fetch all financial_reports
 * Notes: Returns all financial_reports
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/financial_reports', function($request, $response, $args) {
    global $authUser;
	$queryParams = $request->getQueryParams();
	$results = array();
	try {
        if($authUser->role_id != \Constants\ConstUserTypes::Admin) {
            $events = Models\Event::where('user_id', $authUser->id)->get()->toArray();
            foreach($events as $event) {
                $event_id[] = $event['id'];
            }
            if(!empty($event_id)) {
                if(!empty($queryParams['event_id'])) {
                    $event = Models\Event::find($queryParams['event_id']);
                    if(empty($event) || $event->user_id != $authUser->id) {
                        return renderWithJson($results, $message = 'Invalid user', $fields = '', $isError = 1);    
                    } else {
                        $financialReports['data']['delivery_methods'] = Models\Order::with('delivery_methods')->where('event_id', $queryParams['event_id'])->groupBy('delivery_method_id')->select('delivery_method_id')->selectRaw('count("quantity")')->Filter($queryParams)->get()->toArray();
                        $financialReports['data']['sales_channels'] = Models\Order::where('event_id', $queryParams['event_id'])->groupBy('is_booked_via_mobile')->select('is_booked_via_mobile')->selectRaw('count("quantity")')->Filter($queryParams)->get()->toArray();
                        $financialReports['data']['payment_types'] = Models\Order::where('event_id', $queryParams['event_id'])->groupBy('payment_gateway_id')->select('payment_gateway_id')->selectRaw('count("quantity")')->Filter($queryParams)->get()->toArray();
                        $financialReports['data']['event_details'] = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
                    }
                } else {
                    $financialReports['data']['delivery_methods'] = Models\Order::with('delivery_methods')->whereIn('event_id', $event_id)->groupBy('delivery_method_id')->select('delivery_method_id')->selectRaw('count("quantity")')->Filter($queryParams)->get()->toArray();
                    $financialReports['data']['sales_channels'] = Models\Order::whereIn('event_id', $event_id)->groupBy('is_booked_via_mobile')->select('is_booked_via_mobile')->selectRaw('count("quantity")')->Filter($queryParams)->get()->toArray();
                    $financialReports['data']['payment_types'] = Models\Order::whereIn('event_id', $event_id)->groupBy('payment_gateway_id')->select('payment_gateway_id')->selectRaw('count("quantity")')->Filter($queryParams)->get()->toArray();
                }
                $financialReports['data']['overall']['total_sales'] = Models\Order::whereIn('event_id', $event_id)->Filter($queryParams)->sum('quantity');
                $total_revenue = Models\Order::whereIn('event_id', $event_id)->Filter($queryParams)->sum('total_amount');
                $financialReports['data']['overall']['total_revenue'] = round($total_revenue);
            } else {
                return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
            }                
        } else {
            $financialReports['data']['overall']['total_sales'] = Models\Order::Filter($queryParams)->sum('quantity');
            $total_revenue = Models\Order::Filter($queryParams)->sum('total_amount');
            $financialReports['data']['overall']['total_revenue'] = round($total_revenue);
            $financialReports['data']['delivery_methods'] = Models\Order::with('delivery_methods')->groupBy('delivery_method_id')->Filter($queryParams)->select('delivery_method_id')->selectRaw('count("quantity")')->get()->toArray();
            $financialReports['data']['sales_channels'] = Models\Order::groupBy('is_booked_via_mobile')->Filter($queryParams)->select('is_booked_via_mobile')->selectRaw('count("quantity")')->get()->toArray();
            $financialReports['data']['payment_types'] = Models\Order::groupBy('payment_gateway_id')->Filter($queryParams)->select('payment_gateway_id')->selectRaw('count("quantity")')->get()->toArray();
            if(!empty($queryParams['event_id'])) {
                $financialReports['data']['event_details'] = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
            }
        }
        $data = $financialReports['data'];
        unset($financialReports['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $financialReports
        );
        return renderWithJson($results);
	}
	catch(Exception $e) {
		return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
	}
})->add(new ACL('canListFinancialReport'));


/**
 * GET participantReportsGet
 * Summary: Fetch all participant_reports
 * Notes: Returns all participant_reports
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/participant_reports', function($request, $response, $args) {
    global $authUser;
	$queryParams = $request->getQueryParams();
	$results = array();
	try {
        if($authUser->role_id != \Constants\ConstUserTypes::Admin) {
            if(!empty($queryParams['event_id'])) {
                $event = Models\Event::find($queryParams['event_id']);
                if(empty($event) || $event->user_id != $authUser->id) {
                    return renderWithJson($results, $message = 'Invalid user', $fields = '', $isError = 1);    
                } else {
                    if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
                        $participantReports['data'] = Models\OrderItem::with('users', 'events', 'order', 'venue_zone_section_seats', 'price_type')->Filter($queryParams)->get()->toArray();    
                    } else {
                        $count = !empty($queryParams['limit']) ? $queryParams['limit'] : PAGE_LIMIT;
                        $participantReports = Models\OrderItem::with('users', 'events', 'order', 'venue_zone_section_seats', 'price_type')->Filter($queryParams)->paginate($count)->toArray();
                    }
                } 
            } else {
                return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);                
            }
        } else {
            if(!empty($queryParams['event_id'])) {
                if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
                    $participantReports['data'] = Models\OrderItem::with('users', 'events', 'order', 'venue_zone_section_seats', 'price_type')->Filter($queryParams)->get()->toArray();    
                } else {
                    $count = !empty($queryParams['limit']) ? $queryParams['limit'] : PAGE_LIMIT;
                    $participantReports = Models\OrderItem::with('users', 'events', 'order', 'venue_zone_section_seats', 'price_type')->Filter($queryParams)->paginate($count)->toArray();
                }
            } else {
                return renderWithJson($results, $message = 'Please select an event', $fields = '', $isError = 1);
            }
        }
		$data = $participantReports['data'];
        $event_details = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
        $total_details['total_quantity'] = Models\Order::where('event_id', $queryParams['event_id'])->sum('quantity'); 
        $total_details['total_revenue'] = Models\Order::where('event_id', $queryParams['event_id'])->sum('total_amount');
		unset($participantReports['data']);
		$results = array(
			'data' => $data,
            'event_details' => $event_details,
            'total_details' => $total_details,
			'_metadata' => $participantReports
		);
		return renderWithJson($results);
	}
	catch(Exception $e) {
		return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
	}
})->add(new ACL('canListParticipantReport'));

/**
 * GET visitorReportsGet
 * Summary: Fetch all visitor_reports
 * Notes: Returns all visitor_reports
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/visitor_reports', function($request, $response, $args) {
    global $authUser, $capsule;
	$queryParams = $request->getQueryParams();
	$results = array();
	try {
        if($authUser->role_id != \Constants\ConstUserTypes::Admin) {
            $events = Models\Event::where('user_id', $authUser->id)->get()->toArray();
            foreach($events as $event) {
                $event_id[] = $event['id'];
            }
            if(!empty($event_id)) {
                if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
                    $where_query =  !empty($queryParams['event_id']) ? 'WHERE event_id = ' . $queryParams['event_id'] : 'WHERE "event_id" IN (' . implode(',', $event_id) . ')';
                    if(!empty($queryParams['start_date'])) {
                        $where_query .= ' AND created_at >= \'' . $queryParams['start_date'] .' 00:00:00\' ';
                    }
                    if(!empty($queryParams['end_date'])) {
                        $where_query .= ' AND created_at <= \'' . $queryParams['start_date'] .' 23:59:59\' ';
                    }
                    $visitor_report_datewise_member = $capsule::select('SELECT created_at::DATE, COUNT("id") FROM "event_views" ' . $where_query . ' AND user_id != ? GROUP BY created_at::DATE ORDER BY created_at DESC', array(0));
                    $visitorReports['data']['datewise']['member'] = (array)$visitor_report_datewise_member; 
                    $visitor_report_datewise_visitor = $capsule::select('SELECT created_at::DATE, COUNT("id") FROM "event_views" ' . $where_query . ' AND user_id = ? GROUP BY created_at::DATE ORDER BY created_at DESC', array(0));
                    $visitorReports['data']['datewise']['visitor'] = (array)$visitor_report_datewise_visitor;
                    $visitorReports['data']['overall']['visitByMember'] = Models\EventView::whereIn('event_id', $event_id)->where('user_id', '!=', 0)->Filter($queryParams)->get()->count();
                    $visitorReports['data']['overall']['visitByVisitor'] = Models\EventView::whereIn('event_id', $event_id)->where('user_id', 0)->Filter($queryParams)->get()->count();
                    $visitorReports['data']['overall']['total_visit'] = Models\EventView::whereIn('event_id', $event_id)->Filter($queryParams)->get()->count();
                } else {
                    $count = !empty($queryParams['limit']) ? $queryParams['limit'] : PAGE_LIMIT;
                    $page = !empty($queryParams['page']) ? $queryParams['page'] : 1;
                    $offset = ($page-1) * $count;
                    if(!empty($queryParams['event_id'])) {
                        $event = Models\Event::find($queryParams['event_id']);
                        if(empty($event) || $event->user_id != $authUser->id) {
                            return renderWithJson($results, $message = 'Invalid user', $fields = '', $isError = 1);    
                        }
                    }
                    $where_query =  !empty($queryParams['event_id']) ? 'WHERE event_id = ' . $queryParams['event_id'] : 'WHERE "event_id" IN (' . implode(',', $event_id) . ')';
                    if(!empty($queryParams['start_date'])) {
                        $where_query .= ' AND created_at >= \'' . $queryParams['start_date'] .' 00:00:00\' ';
                    }
                    if(!empty($queryParams['end_date'])) {
                        $where_query .= ' AND created_at <= \'' . $queryParams['start_date'] .' 23:59:59\' ';
                    }
                    $total_records = Models\EventView::Filter($queryParams)->selectRaw('created_at::DATE as date')->selectRaw('count("id")')->groupBy('date')->get()->toArray();
                    $visitor_report_datewise_member = $capsule::select('SELECT created_at::DATE, COUNT("id") FROM "event_views" ' . $where_query . ' AND user_id != ? GROUP BY created_at::DATE ORDER BY created_at DESC LIMIT ? OFFSET ?', array(0, $count, $offset));
                    $visitorReports['data']['datewise']['member'] = (array)$visitor_report_datewise_member; 
                    $visitor_report_datewise_visitor = $capsule::select('SELECT created_at::DATE, COUNT("id") FROM "event_views" ' . $where_query . ' AND user_id = ? GROUP BY created_at::DATE ORDER BY created_at DESC LIMIT ? OFFSET ?', array(0, $count, $offset));
                    $visitorReports['data']['datewise']['visitor'] = (array)$visitor_report_datewise_visitor;
                    $visitorReports['data']['overall']['visitByMember'] = Models\EventView::whereIn('event_id', $event_id)->where('user_id', '!=', 0)->Filter($queryParams)->get()->count();
                    $visitorReports['data']['overall']['visitByVisitor'] = Models\EventView::whereIn('event_id', $event_id)->where('user_id', 0)->Filter($queryParams)->get()->count();
                    $visitorReports['data']['overall']['total_visit'] = Models\EventView::whereIn('event_id', $event_id)->Filter($queryParams)->get()->count();
                    $_metadata = setMetaData($count, $page, $offset, $total_records);
                }
            } else {
                return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
            }
        } else {
            if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
                $visitorReports['data']['datewise']['member'] = Models\EventView::where('user_id', '!=', 0)->Filter($queryParams)->selectRaw('created_at::DATE as date')->selectRaw('count("id")')->groupBy('date')->get()->toArray();
                $visitorReports['data']['datewise']['visitor'] = Models\EventView::where('user_id', 0)->Filter($queryParams)->selectRaw('created_at::DATE as date')->selectRaw('count("id")')->groupBy('date')->get()->toArray();
                $visitorReports['data']['overall']['visitByMember'] = Models\EventView::where('user_id', '!=', 0)->Filter($queryParams)->get()->count();
                $visitorReports['data']['overall']['visitByVisitor'] = Models\EventView::where('user_id', 0)->Filter($queryParams)->get()->count();
                $visitorReports['data']['overall']['total_visit'] = Models\EventView::Filter($queryParams)->get()->count();
            } else {
                $count = !empty($queryParams['limit']) ? $queryParams['limit'] : PAGE_LIMIT;
                $page = !empty($queryParams['page']) ? $queryParams['page'] : 1;
                $offset = ($page-1) * $count;
                $where_query =  !empty($queryParams['event_id']) ? 'WHERE event_id = ' . $queryParams['event_id'] : '';
                if(!empty($queryParams['start_date'])) {
                    $where_query .= ' AND created_at >= \'' . $queryParams['start_date'] .' 00:00:00\' ';
                }
                if(!empty($queryParams['end_date'])) {
                    $where_query .= ' AND created_at <= \'' . $queryParams['start_date'] .' 23:59:59\' ';
                }
                $total_records = Models\EventView::Filter($queryParams)->selectRaw('created_at::DATE as date')->selectRaw('count("id")')->groupBy('date')->get()->toArray();
                $visitor_report_datewise_member = $capsule::select('SELECT created_at::DATE, COUNT("id") FROM "event_views" where user_id != ? GROUP BY created_at::DATE ORDER BY created_at DESC LIMIT ? OFFSET ?', array(0, $count, $offset));
                $visitorReports['data']['datewise']['member'] = (array)$visitor_report_datewise_member; 
                $visitor_report_datewise_visitor = $capsule::select('SELECT created_at::DATE, COUNT("id") FROM "event_views" where user_id = ? GROUP BY created_at::DATE ORDER BY created_at DESC LIMIT ? OFFSET ?', array(0, $count, $offset));
                $visitorReports['data']['datewise']['visitor'] = (array)$visitor_report_datewise_visitor;
                $visitorReports['data']['overall']['visitByMember'] = Models\EventView::where('user_id', '!=', 0)->Filter($queryParams)->get()->count();
                $visitorReports['data']['overall']['visitByVisitor'] = Models\EventView::where('user_id', 0)->Filter($queryParams)->get()->count();
                $visitorReports['data']['overall']['total_visit'] = Models\EventView::Filter($queryParams)->get()->count();
                $_metadata = setMetaData($count, $page, $offset, $total_records);
            }
        }
        if(!empty($queryParams['event_id'])) {
            $visitorReports['data']['event_details'] = Models\Event::with('attachments', 'event_schedule')->where('id', $queryParams['event_id'])->get()->toArray();
        }        
		$data = $visitorReports['data'];
		unset($visitorReports['data']);
		$results = array(
			'data' => $data,
			'_metadata' => $_metadata
		);
		return renderWithJson($results);
	}
	catch(Exception $e) {
		return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
	}
})->add(new ACL('canListVisitorReport'));

/**
 * DELETE categoriesCategoryIdDelete
 * Summary: Delete Category
 * Notes: Deletes a single Category based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/categories/{categoryId}', function ($request, $response, $args)
{
    $category = Models\Category::find($request->getAttribute('categoryId'));
    try {
        $category->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Category could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteCategory'));
/**
 * GET categoriesCategoryIdGet
 * Summary: Fetch Category
 * Notes: Returns a Category based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/categories/{categoryId}', function ($request, $response, $args)
{
    $category = Models\Category::find($request->getAttribute('categoryId'));
    $result = array();
    if (!empty($category)) {
        $result['data'] = $category->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewCategory'));
/**
 * PUT categoriesCategoryIdPut
 * Summary: Update Category by its id
 * Notes: Update Category by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/categories/{categoryId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $category = Models\Category::find($request->getAttribute('categoryId'));
    foreach ($args as $key => $arg) {
        $category->{$key} = $arg;
    }
    $category->slug = Inflector::slug(strtolower($category->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $category->validate($args);
        if (empty($validationErrorFields)) {
            $category->save();
            $result = $category->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Category could not be updated. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Category could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateCategory'));
/**
 * GET categoriesGet
 * Summary: Fetch all Categories
 * Notes: Returns all Categories from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/categories', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $categories = Models\Category::Filter($queryParams)->paginate(20)->toArray();
        $data = $categories['data'];
        unset($categories['data']);
        if ($queryParams[filter] == 'all') {
            $data = Models\Category::all()->toArray();
        }
        $results = array(
            'data' => $data,
            '_metadata' => $categories
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * POST categoriesPost
 * Summary: Creates a new Category
 * Notes: Creates a new Category
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/categories', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $category = new Models\Category;
    foreach ($args as $key => $arg) {
        $category->{$key} = $arg;
    }
    $category->slug = Inflector::slug(strtolower($category->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $category->validate($args);
        if (empty($validationErrorFields)) {
            $category->save();
            $result['data'] = $category->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Category could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Category could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('canCreateCategory'));
/**
 * DELETE couponsCouponIdDelete
 * Summary: Delete Coupon
 * Notes: Deletes a single Coupon based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/coupons/{couponId}', function ($request, $response, $args)
{
    $coupon = Models\Coupon::find($request->getAttribute('couponId'));
    try {
        $coupon->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Coupon could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteCoupon'));
/**
 * GET couponsCouponIdGet
 * Summary: Fetch Coupon
 * Notes: Returns a Coupon based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/coupons/{couponId}', function ($request, $response, $args)
{
    $coupon = Models\Coupon::find($request->getAttribute('couponId'));
    if (!empty($coupon)) {
        $result['data'] = $coupon->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
    return renderWithJson($result);
})->add(new ACL('canViewCoupon'));
/**
 * PUT couponsCouponIdPut
 * Summary: Update Coupon by its id
 * Notes: Update Coupon by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/coupons/{couponId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $coupon = Models\Coupon::find($request->getAttribute('couponId'));
    foreach ($args as $key => $arg) {
        $coupon->{$key} = $arg;
    }
    $result = array();
    try {
        $validationErrorFields = $coupon->validate($args);
        if (empty($validationErrorFields)) {
            $coupon->save();
            $result = $coupon->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Coupon could not be updated. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Coupon could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateCoupon'));
/**
 * GET couponsGet
 * Summary: Fetch all Coupons
 * Notes: Returns all Coupons from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/coupons', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $coupons = Models\Coupon::Filter($queryParams)->paginate(20)->toArray();
        $data = $coupons['data'];
        unset($coupons['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $coupons
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListCoupon'));
/**
 * POST couponsPost
 * Summary: Creates a new Coupon
 * Notes: Creates a new Coupon
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/coupons', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $coupon = new Models\Coupon;
    foreach ($args as $key => $arg) {
        $coupon->{$key} = $arg;
    }
    $result = array();
    try {
        $validationErrorFields = $coupon->validate($args);
        if (empty($validationErrorFields)) {
            $coupon->save();
            $result['data'] = $coupon->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Coupon could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Coupon could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('canCreateCoupon'));
/**
 * DELETE eventsEventIdDelete
 * Summary: Delete Event
 * Notes: Deletes a single Event based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/events/{eventId}', function ($request, $response, $args)
{
    $event = Models\Event::find($request->getAttribute('eventId'));
    try {
        $event->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Event could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteEvent'));
/**
 * GET eventsEventIdGet
 * Summary: Fetch Event
 * Notes: Returns a Event based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/events/{eventId}', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    try {
        if (!empty($queryParams['event_date'])) {
            $newdate = strtotime('+1 month', strtotime($queryParams['event_date']));
            $enddate = date('Y-m-d', $newdate);
            if (!empty($queryParams['event_end_date'])) {
                $enddate = $queryParams['event_end_date'];
            }
            $eventDates = Models\EventSchedule::where('event_id', $request->getAttribute('eventId'))->whereDate('start_date', '>=', $queryParams['event_date'])->whereDate('end_date', '<=', $enddate)->select('start_date', 'end_date','id')->get()->groupBy(function ($date)
            {
                return Carbon::parse($date->start_date)->format('Y-m-d');
            });
            $event = Models\Event::find($request->getAttribute('eventId'))->toArray();
            $event_zones = Models\EventZone::where('event_id',$request->getAttribute('eventId'))->select('id')->get()->toArray();
            foreach($event_zones as $event_zone){
                $eventzone_ids[] = $event_zone['id'];
            }
            if(!empty($eventzone_ids)){
                $event_zone_min_price = Models\EventZonePrice::whereIn('event_zone_id',$eventzone_ids)->min('price');
                $event['min_event_price'] = $event_zone_min_price;
            }else{
                $event['min_event_price'] = 0;
            }            

            if (!empty($queryParams['event_end_date']) && $queryParams['event_end_date'] == $queryParams['event_date']) {
                $event['event_schedule'] = $eventDates->toArray();
            } else {
                $eventDates = $eventDates->toArray();
                $i = 0;
                foreach ($eventDates as $key => $value) {
                    $event_schedules[$i]['date'] = $key;
                    $event_schedules[$i]['schedule_timing'] = $value;
                    $event_schedules[$i]['event_count'] = count($value);
                    $i++;
                }
                $event['event_schedule'] = $event_schedules;
            }
        } else {
            $event = Models\Event::with('attachments', 'event_schedule', 'category', 'series', 'venue', 'attachment_floor_plan', 'attachment_ticket_price','video')->find($request->getAttribute('eventId'));
            $event_zones = Models\EventZone::where('event_id',$request->getAttribute('eventId'))->select('id')->get()->toArray();
            foreach($event_zones as $event_zone){
                $eventzone_ids[] = $event_zone['id'];
            }
            if(!empty($eventzone_ids)){
            $event_zone_min_price = Models\EventZonePrice::whereIn('event_zone_id',$eventzone_ids)->min('price');           
            $event['min_event_price'] = $event_zone_min_price;
            }else{
                $event['min_event_price'] = 0;
            }             
        }
        if (!empty($_GET['token'])) {
            $oauthAccessToken = Models\OauthAccessToken::where('access_token', $_GET['token'])->first()->toArray();
            if (count($oauthAccessToken) > 0) {
                if (!empty($oauthAccessToken['user_id'])) {
                    $authUser = Models\User::where('username', $oauthAccessToken['user_id'])->first();
                }
            }
        }
        if(!empty($queryParams['type']) && $queryParams['type'] == 'view') {
            $eventView = new Models\EventView;
            if (!empty($authUser['id'])) {
                $eventView->user_id = $authUser['id'];
            }
            $eventView->event_id = $request->getAttribute('eventId');
            $eventView->save();
            $event_views = Models\Event::where('id', '=', $request->getAttribute('eventId'))->first();
            $event_views->increment('view_count', 1);
        }
        $result = array();
        if (!empty($event)) {
            $result['data'] = $event;
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'No record found', '', 1);
        }
        return renderWithJson($result);
    } 
    catch (Exception $e)
    {
        return renderWithJson($result, 'Event could not be shown. Please, try again.', '', 1);
    }
});
/**
 * GET eventsEventdAvailableSeats
 * Summary: Fetch Event AvailableSeats
 * Notes: Returns a Event based on a single ID
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/events/{eventId}/best_available_seats', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $event_id = $request->getAttribute('eventId');
    $result = array();
    if (!empty($args['price_type'])) {
        $i = 0;
        $total_tickets = 0;
        foreach ($args['price_type'] as $price_type_ids) {
            $prices[$i]['id'] = $price_type_ids['id'];
            $prices[$i]['tickets'] = $price_type_ids['tickets'];
            $total_tickets = $total_tickets + $price_type_ids['tickets'];
            $prices[$i]['price'] = $price_type_ids['price'];
            $i++;
        }
    }
    if (!empty($args['event_zone_id'])) {
        $event_zones = Models\EventZone::where('id', $args['event_zone_id'])->select('venue_id', 'available_count', 'id')->first()->toArray();
    } else {
        $event_zones = Models\EventZone::where('event_id', $event_id)->where('available_count', '>=', $total_tickets)->select('venue_id', 'available_count', 'id')->first();
        if (!empty($event_zones)) {
            $event_zones = $event_zones->toArray();
        } else {
            $event_zones['available_count'] = 0;
        }
    }
    if ($event_zones['available_count'] > $total_tickets) {
        $venue = Models\Venue::where('id', $event_zones['venue_id'])->select('is_seat_map')->first()->toArray();
        if ($venue['is_seat_map'] == 0) {
            $j = 0;
            foreach ($args['price_type'] as $price_type_ids) {
                $cart = new Models\Cart;
                $cart->event_id = $event_id;
                $cart->price = $price_type_ids['price'];
                $cart->price_type_id = $price_type_ids['id'];
                $cart->quantity = $price_type_ids['tickets'];
                $cart->venue_zone_section_seat_id = 0;
                $cart->venue_zone_section_id = 0;
                $cart->venue_zone_section_row_id = 0;
                $cart->event_zone_id = $args['event_zone_id'];
                $cart->is_choose_best_availability = 1;
                $cart->session_id = $args['session_id'];
                $cart->event_schedule_id = $args['event_schedule_id'];
                $cart->save();
            }
            $data_result = array();
            // Update seats in carts table
            $cart = Models\Cart::where('session_id', $args['session_id'])->get();
            $data_result['data'] = $cart->toArray();
            return renderWithJson($data_result, 'Tickets are added in cart', '', 0);
        } else {
            $event_zone_section_rows = Models\EventZoneSectionRow::where('event_zone_id', $event_zones['id'])->select('venue_zone_section_row_id', 'venue_zone_section_id')->get()->toArray();
            //get seats from event zone section and row
            foreach ($event_zone_section_rows as $section_rows) {
                $sections_ids[] = $section_rows['venue_zone_section_id'];
                $row_ids[] = $section_rows['venue_zone_section_row_id'];
            }
            $event_zone_seats = Models\VenueZoneSectionSeat::whereIn('venue_zone_section_id', $sections_ids)->whereIn('venue_zone_section_row_id', $row_ids)->select('id')->get()->toArray();
            foreach ($event_zone_seats as $event_zone_seat) {
                $seats[] = $event_zone_seat['id'];
            }
            // Get booking seats from Order item table
            $booking_seats = Models\OrderItem::whereIn('venue_zone_section_seat_id', $event_zone_seats)->select('venue_zone_section_seat_id')->get()->toArray();
            foreach ($booking_seats as $bookingseats) {
                $booked_seats[] = $bookingseats['venue_zone_section_seat_id'];
            }
            if (!empty($booked_seats)) {
                $event_available_seats = array_diff($seats, $booked_seats);
            } else {
                $event_available_seats = $seats;
            }
            if (count($event_available_seats) > 0) {
                /* foreach($event_available_seats as $seats){
                $array[] = $seats['id']; 
                }*/
                $array = $event_available_seats;
                // Sorting
                asort($array);
                $previous = null;
                $result = array();
                $consecutiveArray = array();
                // Slice array by consecutive sequences
                foreach ($array as $number) {
                    if ($number == $previous + 1) {
                        $consecutiveArray[] = $number;
                    } else {
                        $result[] = $consecutiveArray;
                        $consecutiveArray = array(
                            $number
                        );
                    }
                    $previous = $number;
                }
                $result[] = $consecutiveArray;
                // Get length of each sub array
                $count = array_map('count', $result);
                asort($count);
                foreach ($count as $key => $value) {
                    if ($value > $total_tickets) {
                        $ticket_value = $key;
                        break;
                    }
                }
                $data_result = array();
                // Update seats in carts table
                $cart_session_id = updateCartTable($total_tickets, $result[$ticket_value], $request->getAttribute('eventId') , $args['price_type'], $args['event_schedule_id'], $args['event_zone_id'], $args['session_id']);
                $cart = Models\Cart::where('session_id', $cart_session_id)->get();
                $data_result['data'] = $cart->toArray();
            } else {
                return renderWithJson($result, 'Sorry tickets are not available', '', 1);
            }
            return renderWithJson($data_result, 'Tickets are added in cart', '', 0);
        }
    } else {
        return renderWithJson($result, 'Sorry tickets are not available nw', '', 1);
    }
});
/**
 * PUT eventsEventIdPut
 * Summary: Update Event by its id
 * Notes: Update Event by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/events/{eventId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $event = Models\Event::find($request->getAttribute('eventId'));
    foreach ($args as $key => $arg) {
        if (!is_object($arg) && !is_array($arg)) {
            if ($key != 'eventschedule' && $key != 'image' && $key != 'floor_plan' && $key != 'ticket_prices' && $key != 'category' && $key != 'venue' && $key != 'series' && $key != 'id' && $key != 'created_at' && $key != 'updated_at' && $key != 'slug' && $key != 'view_count' && $key != 'order_count' && $key != 'attachments' && $key != 'event_schedule' && $key != 'attachment_floor_plan' && $key != 'attachment_ticket_price' && $key != 'video' && $key != 'min_event_price') {
                $event->{$key} = $arg;
            }
        }
    }
    $event->slug = Inflector::slug(strtolower($event->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $event->validate($args);
        if (empty($validationErrorFields)) {
            $event->save();
            $eventSchedules = Models\EventSchedule::where('event_id', $event->id)->get()->toArray();
            if(!empty($eventSchedules)) {
                foreach($eventSchedules as $event_schedule) {
                    $event_schedule_id[] = $event_schedule['id'];
                }
                if(empty($args['eventschedule'])) {
                    $eventSchedules->whereIn('id', $event_schedule_id)->delete();
                } else {
                    foreach ($args['eventschedule'] as $key => $value) {
                        if (!empty($value['id'])) {
                            $args_event_scedule_id[] = $value['id'];
                        }
                    }
                    if(!empty($event_schedule_id) && !empty($args_event_scedule_id)) {
                        $ids_to_delete = array_diff($event_schedule_id, $args_event_scedule_id);
                        Models\EventSchedule::whereIn('id', $ids_to_delete)->delete();
                    }
                    foreach ($args['eventschedule'] as $key => $value) {                        
                        if (!empty($value['id'])) { 
                            $eventSchedule = Models\EventSchedule::find($value['id']);
                            $eventSchedule->start_date = $value['start_date'];
                            $eventSchedule->end_date = $value['end_date'];
                            $eventSchedule->venue_opens_at = $value['venue_opens_at'];
                            $eventSchedule->event_id = $event->id;
                            $eventSchedule->id = $value['id'];
                            $eventSchedule->save();
                        } else {
                            $eventSchedule = new Models\EventSchedule;
                            $eventSchedule->start_date = $value['start_date'];
                            $eventSchedule->end_date = $value['end_date'];
                            $eventSchedule->venue_opens_at = $value['venue_opens_at'];
                            $eventSchedule->event_id = $event->id;
                            $eventSchedule->save();
                        }    
                    }    
                }
            } else {
                if (!empty($args['eventschedule'])) {
                    foreach ($args['eventschedule'] as $key => $value) {
                        $eventSchedule = new Models\EventSchedule;
                        $eventSchedule->start_date = $value['start_date'];
                        $eventSchedule->end_date = $value['end_date'];
                        $eventSchedule->venue_opens_at = $value['venue_opens_at'];
                        $eventSchedule->event_id = $event->id;
                        $eventSchedule->save();
                    }
                }
            }
            if ((!empty($args['image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['image']))) {
                //Removing and ree inserting new image
                $img = Models\Attachment::where('foreign_id', $request->getAttribute('eventId'))->where('class', 'Event')->first();
                if (!empty($img)) {
                    if (file_exists(APP_PATH . '/media/Event/' . $request->getAttribute('eventId') . '/' . $img['filename'])) {
                        unlink(APP_PATH . '/media/Event/' . $request->getAttribute('eventId') . '/' . $img['filename']);
                        $img->delete();
                    }
                }
                // Removing Thumb folder images
                $mediadir = APP_PATH . '/client/app/images/';
                $whitelist = array(
                    '127.0.0.1',
                    '::1'
                );
                if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                    $mediadir = APP_PATH . '/client/images/';
                }
                foreach (EVENT_THUMB_SIZES as $key => $value) {
                    $list = glob($mediadir . $key . '/' . 'Event' . '/' . $request->getAttribute('eventId') . '.*');
                    @unlink($list[0]);
                }
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/Event/' . $event->id)) {
                    mkdir(APP_PATH . '/media/Event/' . $event->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['image'];
                $dest = APP_PATH . '/media/Event/' . $event->id . '/' . $args['image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'Event/' . $event->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $event->id;
                $attachment->class = 'Event';
                $attachment->save();
            }
            if ((!empty($args['floor_plan'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['floor_plan']))) {
                //Removing and ree inserting new image
                $img = Models\Attachment::where('foreign_id', $request->getAttribute('eventId'))->where('class', 'EventFloorPlan')->first();
                if (!empty($img)) {
                    if (file_exists(APP_PATH . '/media/EventFloorPlan/' . $request->getAttribute('eventId') . '/' . $img['filename'])) {
                        unlink(APP_PATH . '/media/EventFloorPlan/' . $request->getAttribute('eventId') . '/' . $img['filename']);
                        $img->delete();
                    }
                }
                // Removing Thumb folder images
                $mediadir = APP_PATH . '/client/app/images/';
                $whitelist = array(
                    '127.0.0.1',
                    '::1'
                );
                if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                    $mediadir = APP_PATH . '/client/images/';
                }
                foreach (EVENT_FLOOR_PLAN_THUMB_SIZES as $key => $value) {
                    $list = glob($mediadir . $key . '/' . 'EventFloorPlan' . '/' . $request->getAttribute('eventId') . '.*');
                    @unlink($list[0]);
                }
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/EventFloorPlan/' . $event->id)) {
                    mkdir(APP_PATH . '/media/EventFloorPlan/' . $event->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['floor_plan'];
                $dest = APP_PATH . '/media/EventFloorPlan/' . $event->id . '/' . $args['floor_plan'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['floor_plan'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'EventFloorPlan/' . $event->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $event->id;
                $attachment->class = 'EventFloorPlan';
                $attachment->save();
            }
            if ((!empty($args['ticket_prices'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['ticket_prices']))) {
                //Removing and ree inserting new image
                $img = Models\Attachment::where('foreign_id', $request->getAttribute('eventId'))->where('class', 'TicketPrices')->first();
                if (!empty($img)) {
                    if (file_exists(APP_PATH . '/media/TicketPrices/' . $request->getAttribute('eventId') . '/' . $img['filename'])) {
                        unlink(APP_PATH . '/media/TicketPrices/' . $request->getAttribute('eventId') . '/' . $img['filename']);
                        $img->delete();
                    }
                }
                // Removing Thumb folder images
                $mediadir = APP_PATH . '/client/app/images/';
                $whitelist = array(
                    '127.0.0.1',
                    '::1'
                );
                if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                    $mediadir = APP_PATH . '/client/images/';
                }
                foreach (TICKET_PRICES_THUMB_SIZES as $key => $value) {
                    $list = glob($mediadir . $key . '/' . 'TicketPrices' . '/' . $request->getAttribute('eventId') . '.*');
                    @unlink($list[0]);
                }
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/TicketPrices/' . $event->id)) {
                    mkdir(APP_PATH . '/media/TicketPrices/' . $event->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['ticket_prices'];
                $dest = APP_PATH . '/media/TicketPrices/' . $event->id . '/' . $args['ticket_prices'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['ticket_prices'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'TicketPrices/' . $event->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $event->id;
                $attachment->class = 'TicketPrices';
                $attachment->save();
            }
            if(!empty($args['video'])){
                     saveVideo('EventVideo',$args['video'],$event->id);
            }   
            $whitelist = array(
                '127.0.0.1',
                '::1'
            );
            if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                if (!file_exists(APP_PATH . '/client/images/EventVideo/' . $event->id)) {
                    mkdir(APP_PATH . '/client/images/EventVideo/' . $event->id, 0777, true);
                }
                file_put_contents(APP_PATH . '/client/images/EventVideo/' . $event->id . '/' . $args['video'], $args['video']);
            } else {
                if (!file_exists(APP_PATH . '/client/app/images/EventVideo/' . $event->id)) {
                    mkdir(APP_PATH . '/client/app/images/EventVideo/' . $event->id, 0777, true);
                }
                file_put_contents(APP_PATH . '/client/app/images/EventVideo/' . $event->id . '/' . $args['video'], $args['video']);
            }                            
            $event = Models\Event::with('event_schedule', 'attachments', 'attachment_floor_plan', 'attachment_ticket_price','video')->find($event->id);
            $result = $event->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Event could not be updated. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Event could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateEvent'));
/**
 * GET venue zone avaialbility count
 * Summary: Fetch Event
 * Notes: Returns a Event based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/events/{eventId}/venue/{venueId}', function ($request, $response, $args)
{
    $venue = Models\Venue::where('id', $request->getAttribute('venueId'))->first()->toArray();
    if (!empty($venue) && $venue['is_seat_map'] == 0) {
        $venueZones = Models\Venue::with('attachments', 'event_venue_zone')->where('id', $request->getAttribute('venueId'))->get()->toArray();
    } else {
        $venueZones = Models\Venue::with('attachments', 'venue_zone', 'event_zone')->where('id', $request->getAttribute('venueId'))->get()->toArray();
    }
    $eventSchedule = Models\EventSchedule::where('event_id', $request->getAttribute('eventId'))->get()->toArray();
    $event_zones = Models\EventZone::where('event_id',$request->getAttribute('eventId'))->select('id')->get()->toArray();
    foreach($event_zones as $event_zone){
        $eventzone_ids[] = $event_zone['id'];
    }
    $event_zone_min_price = Models\EventZonePrice::whereIn('event_zone_id',$eventzone_ids)->min('price');    
    $event = Models\Event::with('attachments','venue','category')->where('id', $request->getAttribute('eventId'))->get()->toArray();
    $event['min_event_price'] = $event_zone_min_price;   
    $i = 0;
    // year month based split $array
    foreach ($eventSchedule as $event_Schedule) {
        $start_date = $event_Schedule['start_date'];
        $start = new DateTime($start_date);
        $search_date = $start->format('d');
        $search_year = $start->format('Y');
        $search_month = $start->format('m');
        $sched_mins = $start->format('H:i');
        $end_date = $event_Schedule['end_date'];
        $end = new DateTime($end_date);
        $serachend_date = $end->format('d');
        $search_end_year = $end->format('Y');
        $search_end_month = $end->format('m');
        $end_mins = $end->format('H:i');
        $sched_end_mins = $end->format('H:i');
        $schedule_arr[$i]['year'] = $search_year;
        $schedule_arr[$i][$search_year]['month'][] = $search_month;
        array_unique($schedule_arr[$i][$search_year]['month']);
        $schedule_arr[$i][$search_year]['month'][$search_month][$search_date]['start_time'] = $sched_mins;
        $schedule_arr[$i][$search_year]['month'][$search_month][$search_date]['end_time'] = $end_mins;
        $schedule_arr[$i]['year'] = $search_end_year;
        $schedule_arr[$i][$search_end_year]['month'][] = $search_end_month;
        array_unique($schedule_arr[$i][$search_end_year]['month']);
        $schedule_arr[$i][$search_end_year]['month'][$search_end_month][$serachend_date]['start_time'] = $sched_end_mins;
        $schedule_arr[$i][$search_end_year]['month'][$search_end_month][$serachend_date]['end_time'] = $sched_end_mins;
        $i++;
    }
    $venueZones['EventSchedulePeriod'] = $schedule_arr;
    $venueZones['EventSchedule'] = $eventSchedule;
    $venueZones['Event'] = $event;
    $result = array();
    try {
        if (!empty($venueZones)) {
            $result['data'] = $venueZones;
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'No record found', '', 1);
        }
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * GET event venue zone section seats
 * Summary: Fetch Seats for venue zone
 * Notes: Returns a venue zone based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/events/{eventId}/venue_zones/{venueZoneId}/seats', function ($request, $response, $args)
{
    $venueZones = Models\VenueZone::with('attachments', 'venue_zone_sections', 'venue_zone_section_row', 'venue_zone_section_seats')->where('id', $request->getAttribute('venueZoneId'))->first()->toArray();
    $venueZonePreviews = Models\VenueZonePreview::with('attachments')->where('venue_zone_id', $request->getAttribute('venueZoneId'))->get()->toArray();
    $event_prices = Models\EventZone::with('event_zone_prices', 'event_zone_sections', 'event_zone_section_rows')->where('event_id', $request->getAttribute('eventId'))->where('venue_zone_id', $request->getAttribute('venueZoneId'))->get()->toArray();
    $price_types = Models\PriceType::all()->toArray();
    $orders = Models\Order::with('order_items')->where('event_id', $request->getAttribute('eventId'))->get()->toArray();
    $event = Models\Event::find($request->getAttribute('eventId'))->toArray();
    $event_zones = Models\EventZone::where('event_id',$request->getAttribute('eventId'))->select('id')->get()->toArray();
    foreach($event_zones as $event_zone){
        $eventzone_ids[] = $event_zone['id'];
    }    
    $event = Models\Event::with('attachments','category','venue')->where('id', $request->getAttribute('eventId'))->get()->toArray();
    $event['min_event_price'] = $event_zone_min_price;     
    $eventschedule = Models\EventSchedule::where('event_id', $request->getAttribute('eventId'))->get()->toArray();
    if (!empty($orders)) {
        foreach ($orders as $order) {
            foreach ($order['order_items'] as $order_item) {
                $venue_available_seats[] = $order_item['venue_zone_section_seat_id'];
            }
        }
    }
    foreach ($venueZones['venue_zone_section_seats'] as $key => $venue_zone_seats) {
        if (in_array($venue_zone_seats['id'], $venue_available_seats)) {
            $venue_zone_seats['is_available'] = 0;
        } else {
            $venue_zone_seats['is_available'] = 1;
        }
        $venueZones['venue_zone_section_seats'][$key] = $venue_zone_seats;
    }
    $venueZones['venue_zone_preview'] = $venueZonePreviews;
    $venueZones['event_prices'] = $event_prices;
    $venueZones['price_types'] = $price_types;
    $venueZones['Event'] = $event;
    $venueZones['EventSchedule'] = $eventschedule;
    $result = array();
    try {
        if (!empty($venueZones)) {
            $result['data'] = $venueZones;
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'No record found', '', 1);
        }
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * GET eventsGet
 * Summary: Fetch all Events
 * Notes: Returns all Events from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/events', function ($request, $response, $args)
{
    if (!empty($_GET['token'])) {
        $authUser = getUserDetails($_GET['token']);
    }
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
       $event =  Models\Event::with('attachments', 'event_schedule', 'category', 'series', 'venue', 'attachment_floor_plan', 'attachment_ticket_price');
        if(!empty($authUser['role_id'] == \Constants\ConstUserTypes::EventOrganizer))
        {
            $event->where('user_id',$authUser['id']);
        }       
        if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
            $events['data'] = $event->Filter($queryParams)->get()->toArray();
            if (!empty($queryParams['filter']) && $queryParams['filter'] == 'hot_events') {
                if (HOT_EVENTS == 'most view') {
                    $events['data'] = $event->orderBy('view_count', 'DESC')->get()->toArray();
                } else {
                    $events['data'] = $event->orderBy('order_count', 'DESC')->get()->toArray();
                }
            }    
        } else {
            $count = !empty($queryParams['limit']) ? $queryParams['limit'] : PAGE_LIMIT;
            $events = $event->Filter($queryParams)->paginate($count)->toArray();
            if (!empty($queryParams['filter']) && $queryParams['filter'] == 'hot_events') {
                if (HOT_EVENTS == 'most view') {
                    $events = $event->orderBy('view_count', 'DESC')->paginate($count)->toArray();
                } else {
                    $events = $event->orderBy('order_count', 'DESC')->paginate($count)->toArray();
                }
            }
        }
        $data = $events['data'];
        unset($events['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $events
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * POST eventsPost
 * Summary: Creates a new Event
 * Notes: Creates a new Event
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/events', function ($request, $response, $args)
{
    global $authUser;
    $args = $request->getParsedBody();
    $event = new Models\Event;
    foreach ($args as $key => $arg) {
        if ($key != 'eventschedule' && $key != 'image' && $key != 'floor_plan' && $key != 'ticket_prices' && $key != 'video') {
            $event->{$key} = $arg;
        }
    }
    $event->slug = Inflector::slug(strtolower($event->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $event->validate($args);
        if (empty($validationErrorFields)) {
            $event->event_status_id = \Constants\EventStatus::Open;
            $event->user_id = $authUser->id;
            $event->save();
            if (!empty($args['eventschedule'])) {
                foreach ($args['eventschedule'] as $key => $value) {
                    $eventSchedule = new Models\EventSchedule;
                    $eventSchedule->start_date = $value['start_date'];
                    $eventSchedule->end_date = $value['end_date'];
                    $eventSchedule->venue_opens_at = $value['venue_opens_at'];
                    $eventSchedule->event_id = $event->id;
                    $eventSchedule->save();
                }
            }
            if ((!empty($args['image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['image']))) {
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/Event/' . $event->id)) {
                    mkdir(APP_PATH . '/media/Event/' . $event->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['image'];
                $dest = APP_PATH . '/media/Event/' . $event->id . '/' . $args['image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'Event/' . $event->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $event->id;
                $attachment->class = 'Event';
                $attachment->save();
            }
            if ((!empty($args['floor_plan'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['floor_plan']))) {
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/EventFloorPlan/' . $event->id)) {
                    mkdir(APP_PATH . '/media/EventFloorPlan/' . $event->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['floor_plan'];
                $dest = APP_PATH . '/media/EventFloorPlan/' . $event->id . '/' . $args['floor_plan'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['floor_plan'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'EventFloorPlan/' . $event->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $event->id;
                $attachment->class = 'EventFloorPlan';
                $attachment->save();
            }
            if ((!empty($args['ticket_prices'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['ticket_prices']))) {
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/TicketPrices/' . $event->id)) {
                    mkdir(APP_PATH . '/media/TicketPrices/' . $event->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['ticket_prices'];
                $dest = APP_PATH . '/media/TicketPrices/' . $event->id . '/' . $args['ticket_prices'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['ticket_prices'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'TicketPrices/' . $event->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $event->id;
                $attachment->class = 'TicketPrices';
                $attachment->save();
            }  
                 if(!empty($args['video'])){
                    saveVideo('EventVideo',$args['video'],$event->id);
                 }                                                      
            $event = Models\Event::with('event_schedule', 'attachments', 'attachment_floor_plan', 'attachment_ticket_price','video')->find($event->id);
            $result['data'] = $event->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Event could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Event could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('canCreateEvent'));
/**
 * GET newsGet
 * Summary: Fetch all News
 * Notes: Returns all News from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/news', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $limit = PAGE_LIMIT;
    if ($queryParams['limit']) {
        $limit = $queryParams['limit'];
    }
    $results = array();
    try {
        $news = Models\News::with('attachments', 'news_category')->Filter($queryParams)->paginate($limit)->toArray();
        $data = $news['data'];
        unset($news['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $news
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * DELETE newsNewsIdDelete
 * Summary: Delete News
 * Notes: Deletes a single News based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/news/{newsId}', function ($request, $response, $args)
{
    $news = Models\News::find($request->getAttribute('newsId'));
    try {
        $news->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'News could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteNews'));
/**
 * GET newsNewsIdGet
 * Summary: Fetch News
 * Notes: Returns a News based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/news/{newsId}', function ($request, $response, $args)
{
    $news = Models\News::with('attachments', 'news_category')->find($request->getAttribute('newsId'));
    $prevNews = Models\News::where('id', '<', $request->getAttribute('newsId'))->max('id');
    $prev = Models\News::with('attachments', 'news_category')->find($prevNews);
    $nextNews = Models\News::where('id', '>', $request->getAttribute('newsId'))->min('id');
    $next = Models\News::with('attachments', 'news_category')->find($nextNews);
    $result = array();
    if (!empty($news)) {
        $newsCategories = array();
        foreach ($news['news_category'] as $v) {
            $newsCategories['news_category_id'][] = $v['news_category_id'];
        }
        if (!empty($prev)) {
            $result['prev'] = $prev->toArray();
        }
        if (!empty($next)) {
            $result['next'] = $next->toArray();
        }
        $news = $news->toArray();
        $data = array_merge($news, $newsCategories);
        $result['data'] = $data;
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
    return renderWithJson($result);
});
/**
 * PUT newsNewsIdPut
 * Summary: Update News by its id
 * Notes: Update News by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/news/{newsId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $news = Models\News::find($request->getAttribute('newsId'));
    foreach ($args as $key => $arg) {
        if (!is_object($arg) && !is_array($arg)) {
            if ($key != 'news_category_id' && $key != 'image' && $key != 'news_category') {
                $news->{$key} = $arg;
            }
        }
    }
    $news->slug = Inflector::slug(strtolower($news->title) , '-');
    $result = array();
    try {
        $validationErrorFields = $news->validate($args);
        if (empty($validationErrorFields)) {
            $news->save();
            $newsCategory = Models\NewsNewsCategory::where('news_id', $news->id)->delete();
            $newsCategories = $args['news_category_id'];
            foreach ($newsCategories as $key => $newsCategory) {
                $newsNewsCategory = new Models\NewsNewsCategory;
                $newsNewsCategory->news_id = $news->id;
                $newsNewsCategory->news_category_id = $newsCategory;
                $newsNewsCategory->save();
            }
            if ((!empty($args['image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['image']))) {
                //Removing and ree inserting new image
                $img = Models\Attachment::where('foreign_id', $request->getAttribute('newsId'))->where('class', 'News')->first();
                if (!empty($img)) {
                    if (file_exists(APP_PATH . '/media/News/' . $request->getAttribute('newsId') . '/' . $img['filename'])) {
                        unlink(APP_PATH . '/media/News/' . $request->getAttribute('newsId') . '/' . $img['filename']);
                        $img->delete();
                    }
                }
                // Removing Thumb folder images
                $mediadir = APP_PATH . '/client/app/images/';
                $whitelist = array(
                    '127.0.0.1',
                    '::1'
                );
                if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                    $mediadir = APP_PATH . '/client/images/';
                }
                foreach (NEWS_THUMB_SIZES as $key => $value) {
                    $list = glob($mediadir . $key . '/' . 'News' . '/' . $request->getAttribute('newsId') . '.*');
                    @unlink($list[0]);
                }
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/News/' . $news->id)) {
                    mkdir(APP_PATH . '/media/News/' . $news->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['image'];
                $dest = APP_PATH . '/media/News/' . $news->id . '/' . $args['image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'News/' . $news->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $news->id;
                $attachment->class = 'News';
                $attachment->save();
            } else {
                $result['data'] = $news;
            }
            $news = Models\News::with('news_category', 'attachments')->find($news->id);
            $result['data'] = $news->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'News could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'News could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateNews'));
/**
 * POST newsPost
 * Summary: Creates a new News
 * Notes: Creates a new News
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/news', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $news = new Models\News;
    foreach ($args as $key => $arg) {
        if ($key != 'image' && $key != 'news_category_id') {
            $news->{$key} = $arg;
        }
    }
    $news->slug = Inflector::slug(strtolower($news->title) , '-');
    $result = array();
    try {
        $validationErrorFields = $news->validate($args);
        if (empty($validationErrorFields)) {
            $news->save();
            $newsCategories = $args['news_category_id'];
            foreach ($newsCategories as $key => $newsCategory) {
                $newsNewsCategory = new Models\NewsNewsCategory;
                $newsNewsCategory->news_id = $news->id;
                $newsNewsCategory->news_category_id = $newsCategory;
                $newsNewsCategory->save();
            }
            if ((!empty($args['image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['image']))) {
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/News/' . $news->id)) {
                    mkdir(APP_PATH . '/media/News/' . $news->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['image'];
                $dest = APP_PATH . '/media/News/' . $news->id . '/' . $args['image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'News/' . $news->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $news->id;
                $attachment->class = 'News';
                $attachment->save();
            } else {
                $result['data'] = $news;
            }
            $news = Models\News::with('news_category', 'attachments')->find($news->id);
            $result['data'] = $news->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'News could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'News could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('canCreateNews'));
/**
 * DELETE eventZonesEventZoneIdDelete
 * Summary: Delete Event Zone
 * Notes: Deletes a single Event Zone based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/event_zones/{eventZoneId}', function ($request, $response, $args)
{
    $eventZone = Models\EventZone::find($request->getAttribute('eventZoneId'));
    try {
        $eventZone->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Event zone could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteEventZone'));
/**
 * GET eventZonesEventZoneIdGet
 * Summary: Fetch Event Zone
 * Notes: Returns a Event Zone based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/event_zones/{eventZoneId}', function ($request, $response, $args)
{
    $eventZone = Models\EventZone::with('event', 'venue', 'venue_zone', 'event_zone_sections', 'event_zone_section_rows', 'event_zone_prices')->find($request->getAttribute('eventZoneId'));
    $result = array();
    if (!empty($eventZone)) {
        $result['data'] = $eventZone->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewEventZone'));
/**
 * PUT eventZonesEventZoneIdPut
 * Summary: Update Event Zone by its id
 * Notes: Update Event Zone by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/event_zones/{eventZoneId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $eventZone = Models\EventZone::find($request->getAttribute('eventZoneId'));
    foreach ($args as $key => $arg) {
        if ($key != 'eventsection' && $key != 'id' && $key != 'created_at' && $key != 'updated_at' && $key != 'event' && $key != 'venue' && $key != 'venue_zone') {
            $eventZone->{$key} = $arg;
            $eventZone->is_available = 1;
        }
    }
    $result = array();
    try {
        $validationErrorFields = $eventZone->validate($args);
        if (empty($validationErrorFields)) {
            $eventZone->save();
            $eventZoneSection = Models\EventZoneSection::where('event_zone_id', $eventZone->id)->delete();
            $eventZoneSectionRow = Models\EventZoneSectionRow::where('event_zone_id', $eventZone->id)->delete();
            $eventZonePrice = Models\EventZonePrice::where('event_zone_id', $eventZone->id)->delete();
            if (!empty($args['eventsection'])) {
                foreach ($args['eventsection'] as $key => $value) {
                    $eventZoneSection = new Models\EventZoneSection;
                    $venueZoneSectionId = $value['venue_zone_section_id'];
                    $eventZoneSection->venue_zone_section_id = $value['venue_zone_section_id'];
                    $eventZoneSection->event_zone_id = $eventZone->id;
                    $eventZoneSection->save();
                    foreach ($value['eventzonerow'] as $key1 => $valuer) {
                        foreach ($valuer as $key3 => $sectionValue) {
                            $eventZoneSectionRow = new Models\EventZoneSectionRow;
                            $eventZoneSectionRow->venue_zone_section_row_id = $sectionValue;
                            $eventZoneSectionRow->venue_zone_section_id = $venueZoneSectionId;
                            $eventZoneSectionRow->event_zone_id = $eventZone->id;
                            $eventZoneSectionRow->save();
                        }
                    }
                }
            }
            foreach ($value['eventzoneprice'] as $key2 => $values) {
                $eventZonePrice = new Models\EventZonePrice;
                $eventZonePrice->price_type_id = $values['price_type_id'];
                $eventZonePrice->price = $values['price'];
                $eventZonePrice->event_zone_id = $eventZone->id;
                $eventZonePrice->save();
            }
            $event_zone_min_price = Models\EventZonePrice::where('event_zone_id',$eventZone->event_id)->min('price');
            $event_zone_max_price = Models\EventZonePrice::where('event_zone_id',$eventZone->event_id)->max('price');
            $event = Models\Event::find($eventZone->event_id);
            $event->min_price = $event_zone_min_price;
            $event->max_price = $event_zone_max_price;
            $event->save();                
            
            $eventZone = Models\EventZone::with('event_zone_sections', 'event_zone_section_rows', 'event_zone_prices')->find($eventZone->id);
            $result = $eventZone->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Event zone could not be updated. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Event zone could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateEventZone'));
/**
 * GET eventZonesGet
 * Summary: Fetch all Event Zones
 * Notes: Returns all Event Zones from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/event_zones', function ($request, $response, $args)
{
    global $authUser;
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $eventZone = Models\EventZone::with('event', 'venue', 'venue_zone', 'event_zone_sections', 'event_zone_section_rows', 'event_zone_prices');
        if(!empty($authUser['role_id'] == \Constants\ConstUserTypes::EventOrganizer))
        {
            $events = Models\Event::where('user_id',$authUser['id'])->select('id')->get()->toArray();
            foreach($events as $values)
            {
               $eventsValue =  $values['id'];
               $eventZone->where('event_id',$eventsValue);
            }
        }
        $eventZones = $eventZone->Filter($queryParams)->paginate(20)->toArray();
        $data = $eventZones['data'];
        unset($eventZones['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $eventZones
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListEventZone'));
/**
 * POST eventZonesPost
 * Summary: Creates a new Event Zone
 * Notes: Creates a new Event Zone
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/event_zones', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $eventZone = new Models\EventZone;
    foreach ($args as $key => $arg) {
        if ($key != 'eventsection') {
            $eventZone->{$key} = $arg;
            $eventZone->is_available = 1;
        }
    }
    $result = array();
    try {
        $validationErrorFields = $eventZone->validate($args);
        $venueZoneSectionId = array();
        $venueZoneSectionRowId = array();
        foreach ($args['eventsection'] as $key => $value) {
            $venueZoneSectionId = $value['venue_zone_section_id'];
            //$venueZoneSectionRowId = $value['eventzonerow']['venue_zone_section_row_id'] ;
            foreach ($value['eventzonerow'] as $key1 => $valuer) {
                foreach ($valuer as $key3 => $sectionValue) {
                    $venueZoneSectionRowId[] = $sectionValue;
                }
            }
        }
        $evenzoneCheck = checkAlreadyEventZoneSectionRows($eventZone->event_id, $eventZone->venue_id, $eventZone->venue_zone_id, array(
            $venueZoneSectionId
        ) , $venueZoneSectionRowId);
        if ($evenzoneCheck) {
            $validationErrorFields = 'Already exit event';
        }
        if (empty($validationErrorFields)) {
            $venue_zone = Models\VenueZone::where('id', $eventZone->venue_zone_id)->first();
            if (!empty($venue_zone)) {
                $venue_zone = $venue_zone->toArray();
                $available_count = $venue_zone['seat_count'];
                $eventZone->available_count = $available_count;
            }
            $eventZone->save();
            $event_zone_id = $eventZone->id;
            $event_schedules = Models\EventSchedule::where('event_id', $eventZone->event_id)->get()->toArray();
            if (!empty($event_schedules)) {
                foreach ($event_schedules as $event_schedule) {
                    $eventScheduleZone = new Models\EventScheduleZone;
                    $eventScheduleZone->event_zone_id = $event_zone_id;
                    $eventScheduleZone->event_id = $eventZone->event_id;
                    $eventScheduleZone->event_schedule_id = $event_schedule['id'];
                    $eventScheduleZone->available_count = $available_count;
                    $eventScheduleZone->save();
                }
            }
            if (!empty($args['eventsection'])) {
                foreach ($args['eventsection'] as $key => $value) {
                    $eventZoneSection = new Models\EventZoneSection;
                    $evenzoneSectionExists = checkAlreadyEventZoneSection($eventZone->id, $value['venue_zone_section_id']);
                    if (empty($evenzoneSectionExists)) {
                        $venueZoneSectionId = $value['venue_zone_section_id'];
                        $eventZoneSection->venue_zone_section_id = $value['venue_zone_section_id'];
                        $eventZoneSection->event_zone_id = $eventZone->id;
                        $eventZoneSection->save();
                    }
                    foreach ($value['eventzonerow'] as $key1 => $valuer) {
                        foreach ($valuer as $key3 => $sectionValue) {
                            $eventZoneSectionRow = new Models\EventZoneSectionRow;
                            $eventZoneSectionRow->venue_zone_section_row_id = $sectionValue;
                            $eventZoneSectionRow->venue_zone_section_id = $venueZoneSectionId;
                            $eventZoneSectionRow->event_zone_id = $eventZone->id;
                            $eventZoneSectionRow->save();
                        }
                    }
                }
                foreach ($value['eventzoneprice'] as $key2 => $values) {
                    $eventZonePrice = new Models\EventZonePrice;
                    $eventZonePrice->price_type_id = $values['price_type_id'];
                    $eventZonePrice->price = $values['price'];
                    $eventZonePrice->event_zone_id = $eventZone->id;
                    $eventZonePrice->save();
                }
            }
            $event_zone_min_price = Models\EventZonePrice::where('event_zone_id',$eventZone->event_id)->min('price');
            $event_zone_max_price = Models\EventZonePrice::where('event_zone_id',$eventZone->event_id)->max('price');
            $event = Models\Event::find($eventZone->event_id);
            $event->min_price = $event_zone_min_price;
            $event->max_price = $event_zone_max_price;
            $event->save();                
            $eventzone = Models\EventZone::with('event_zone_sections', 'event_zone_section_rows', 'event_zone_prices')->find($eventZone->id);
            $result['data'] = $eventzone->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Event zone could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Event zone could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('canCreateEventZone'));
/**
 * POST seriesPost
 * Summary: Creates a new Series
 * Notes: Creates a new Series
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/series', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $series = new Models\Series;
    foreach ($args as $key => $arg) {
        $series->{$key} = $arg;
    }
    $series->slug = Inflector::slug(strtolower($series->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $series->validate($args);
        if (empty($validationErrorFields)) {
            $series->save();
            $result['data'] = $series->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Series could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Series could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('canCreateSeries'));
$app->GET('/api/v1/series', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $series = Models\Series::Filter($queryParams)->paginate(20)->toArray();
        $data = $series['data'];
        unset($series['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $series
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * DELETE seriesSeriesIdDelete
 * Summary: Delete Series
 * Notes: Deletes a single Series based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/series/{seriesId}', function ($request, $response, $args)
{
    $series = Models\Series::find($request->getAttribute('seriesId'));
    try {
        $series->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Series could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteSeries'));
/**
 * GET seriesSeriesIdGet
 * Summary: Fetch Series
 * Notes: Returns a Series based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/series/{seriesId}', function ($request, $response, $args)
{
    $series = Models\Series::find($request->getAttribute('seriesId'));
    $result = array();
    if (!empty($series)) {
        $result['data'] = $series->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewSeries'));
/**
 * PUT seriesSeriesIdPut
 * Summary: Update Series by its id
 * Notes: Update Series by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/series/{seriesId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $series = Models\Series::find($request->getAttribute('seriesId'));
    foreach ($args as $key => $arg) {
        $series->{$key} = $arg;
    }
    $series->slug = Inflector::slug(strtolower($series->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $series->validate($args);
        if (empty($validationErrorFields)) {
            $series->save();
            $result = $series->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Series could not be updated. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Series could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateSeries'));

/**
 * POST venueZonesPost
 * Summary: Creates a new Venue Zone
 * Notes: Creates a new Venue Zone
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/venue_zones', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $venueZone = new Models\VenueZone;
    foreach ($args as $key => $arg) {
        if ($key != 'image' && $key != 'svg_image') {
            $venueZone->{$key} = $arg;
        }
    }
    $result = array();
    $total_seats = 0;
    try {
        $validationErrorFields = $venueZone->validate($args);
        if ($venueZone['is_having_seat_selection'] == 0) {
            unset($validationErrorFields['svg_image']);
        }
        if (empty($validationErrorFields)) {
            $venueZone->save();
            if ((!empty($args['image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['image']))) {
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/VenueZone/' . $venueZone->id)) {
                    mkdir(APP_PATH . '/media/VenueZone/' . $venueZone->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['image'];
                $dest = APP_PATH . '/media/VenueZone/' . $venueZone->id . '/' . $args['image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'VenueZone/' . $venueZone->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $venueZone->id;
                $attachment->class = 'VenueZone';
                $attachment->save();
            } else {
                $result['data'] = $VenueZone;
            }
            if ($venueZone['is_having_seat_selection'] == 1) {
                if ((!empty($args['svg_image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['svg_image']))) {
                    $attachment = new Models\Attachment;
                    if (!file_exists(APP_PATH . '/media/VenueZone/' . $venueZone->id)) {
                        mkdir(APP_PATH . '/media/VenueZone/' . $venueZone->id, 0777, true);
                    }
                    $src = APP_PATH . '/media/tmp/' . $args['svg_image'];
                    $dest = APP_PATH . '/media/VenueZone/' . $venueZone->id . '/' . $args['svg_image'];
                    copy($src, $dest);
                    unlink($src);
                    list($width, $height) = getimagesize($dest);
                    $attachment->filename = $args['svg_image'];
                    if (!empty($width)) {
                        $attachment->width = $width;
                        $attachment->height = $height;
                    }
                    $attachment->dir = 'VenueZone/' . $venueZone->id;
                    $attachment->amazon_s3_thumb_url = '';
                    $attachment->foreign_id = $venueZone->id;
                    $attachment->class = 'VenueZone';
                    $attachment->save();
                }
                $file_path = APP_PATH . '/media/VenueZone/' . $venueZone->id . '/' . $args['svg_image'];
                // parse svg file and get section, row and seat and update in table process
                $response = file_get_contents($file_path);
                $dom = new DOMDocument();
                $dom->loadXML($response);
                $xpath = new DOMXPath($dom);
                $rootNamespace = $dom->lookupNamespaceUri($dom->namespaceURI);
                $xpath->registerNamespace('svg', $rootNamespace);
                $sectionsLength = $xpath->query('//svg:g[@class="js-seat"]/@id')->length;
                for ($i = 0; $i <= $sectionsLength; $i++) {
                    $venueZoneSection = new Models\VenueZoneSection;
                    $venueZoneSectionRow = new Models\VenueZoneSectionRow;
                    $venueZoneSectionSeat = new Models\VenueZoneSectionSeat;
                    $venueZoneData = $xpath->query('//svg:g[@class="js-seat"]/@id')->item($i)->nodeValue;
                    if ($venueZoneData != "") {
                        $data = explode('-', $venueZoneData);
                        $venueSecName = $data[0];
                        //Section save process
                        $venueZoneSecId = checkAlreadyVenueSecExists($venueSecName, $venueZone->id, $args['venue_id']);
                        if (!empty($venueZoneSecId) && $venueZoneSecId != '') {
                            $venueZoneSectionId = $venueZoneSecId;
                        } else {
                            $venueZoneSection->name = $venueSecName;
                            $venueZoneSection->venue_zone_id = $venueZone->id;
                            $venueZoneSection->venue_id = $args['venue_id'];
                            $venueZoneSection->seat_count = 0;
                            $venueZoneSection->save();
                            $venueZoneSectionId = $venueZoneSection->id;
                        }
                        // Venue zone section row save process
                        $venueRowId = $data[1];
                        $venueZoneSecRowId = checkAlreadyVenueSecRowExists($venueRowId, $venueZoneSectionId);
                        if (!empty($venueZoneSecRowId) && $venueZoneSecRowId != '') {
                            $venueZoneSectionRowId = $venueZoneSecRowId;
                        } else {
                            $venueZoneSectionRow->name = $venueRowId;
                            $venueZoneSectionRow->venue_zone_section_id = $venueZoneSectionId;
                            $venueZoneSectionRow->seat_count = 0;
                            $venueZoneSectionRow->save();
                            $venueZoneSectionRowId = $venueZoneSectionRow->id;
                        }
                        // Venue zone section row seat save process
                        $venueSeatId = $data[2];
                        $seatData = array(
                            'seat_id' => $venueSeatId,
                            'venue_section_row_id' => $venueZoneSectionRowId,
                            'venue_section_id' => $venueZoneSectionId,
                            'venue_id' => $args['venue_id'],
                            'venue_zone_id' => $venueZone->id
                        );
                        $venue_zone_sec_row_seat_id = checkAlreadyVenueSecRowSeatExists($seatData);
                        if (!empty($venue_zone_sec_row_seat_id)) {
                            $venueZoneSectionRowSeatId = $venue_zone_sec_row_seat_id;
                        } else {
                            $venueZoneSectionSeat->venue_id = $args['venue_id'];
                            $venueZoneSectionSeat->venue_zone_id = $venueZone->id;
                            $venueZoneSectionSeat->venue_zone_section_id = $venueZoneSectionId;
                            $venueZoneSectionSeat->venue_zone_section_row_id = $venueZoneSectionRowId;
                            $venueZoneSectionSeat->seat_number = $venueSeatId;
                            $venueZoneSectionSeat->seat_information = $args['name'] . '_seat';
                            $venueZoneSectionSeat->x_position = 0.00;
                            $venueZoneSectionSeat->y_position = 0.00;
                            $venueZoneSectionSeat->is_seat = true;
                            $venueZoneSectionSeat->is_box = false;
                            $venueZoneSectionSeat->box_number = 'null';
                            $venueZoneSectionSeat->save();
                            $venueZoneSectionRowSeatId = $venueZoneSectionSeat->id;
                            $total_seats++;
                        }
                        $id = $venueZoneSectionRowSeatId;
                        $xpath->query('//svg:g[@class="js-seat"]/@id')->item($i)->nodeValue = 'seat-' . $id;
                    }
                }
                file_put_contents(APP_PATH . '/media/VenueZone/' . $venueZone->id . '/' . 'update_' . $args['svg_image'], $dom->saveXML());
                // svg process
                $venueZone->seat_count = $total_seats;
                $venueZone->save();
            }
            $venue_zone = $venueZone->with('attachments')->where('id', $venueZone->id)->get();
            $result['data'] = $venue_zone->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Venue zone could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Venue zone could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('canCreateVenueZone'));
/**
 * DELETE venueZonesVenueZoneIdDelete
 * Summary: Delete Venue Zone
 * Notes: Deletes a single Venue Zone based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/venue_zones/{venueZoneId}', function ($request, $response, $args)
{
    $venueZone = Models\VenueZone::find($request->getAttribute('venueZoneId'));
    $venueZoneId = Models\Event::where('venue_id', $venueZone[venue_id])->get()->count();
    try {
        if ($venueZoneId < 0) {
            $venueZone->delete();
            $result = array(
                'status' => 'success',
            );
        } else {
            $result = array(
                'status' => 'Venue zone could not be deleted. haveing event value'
            );
        }
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Venue zone could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteVenueZone'));
/**
 * GET venueZonesVenueZoneIdGet
 * Summary: Fetch Venue Zone
 * Notes: Returns a Venue Zone based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/venue_zones/{venueZoneId}', function ($request, $response, $args)
{
    $venueZone = Models\VenueZone::with('attachments', 'venue', 'venue_zone_sections', 'venue_zone_section_row', 'venue_zone_section_seats')->find($request->getAttribute('venueZoneId'));
    $result = array();
    if (!empty($venueZone)) {
        $result['data'] = $venueZone->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
/**
 * PUT venueZonesVenueZoneIdPut
 * Summary: Update Venue Zone by its id
 * Notes: Update Venue Zone by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/venue_zones/{venueZoneId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $venueZone = Models\VenueZone::find($request->getAttribute('venueZoneId'));
    foreach ($args as $key => $arg) {
        if (!is_object($arg) && !is_array($arg)) {
            if ($key != 'image' && $key != 'svg_image' && $key != 'venue_zone_sections' && $key != 'venue_zone_section_row' && $key != 'venue_zone_section_seats') {
                $venueZone->{$key} = $arg;
            }
        }
    }
    $result = array();
    $total_seats = 0;
    try {
        $validationErrorFields = $venueZone->validate($args);
        if ($venueZone['is_having_seat_selection'] == 0) {
            unset($validationErrorFields['svg_image']);
        }
        if (empty($validationErrorFields)) {
            $venueZone->save();
            if ((!empty($args['image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['image']))) {
                //Removing and ree inserting new image
                $img = Models\Attachment::where('foreign_id', $request->getAttribute('venueZoneId'))->where('class', 'VenueZone')->where('filename', 'LIKE', "%png%")->first();
                if (!empty($img)) {
                    if (file_exists(APP_PATH . '/media/VenueZone/' . $request->getAttribute('venueZoneId') . '/' . $img['filename'])) {
                        unlink(APP_PATH . '/media/VenueZone/' . $request->getAttribute('venueZoneId') . '/' . $img['filename']);
                        $img->delete();
                    }
                }
                // Removing Thumb folder images
                $mediadir = APP_PATH . '/client/app/images/';
                $whitelist = array(
                    '127.0.0.1',
                    '::1'
                );
                if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                    $mediadir = APP_PATH . '/client/images/';
                }
                foreach (THUMB_SIZES as $key => $value) {
                    $list = glob($mediadir . $key . '/' . 'VenueZone' . '/' . $request->getAttribute('venueZoneId') . '.*');
                    @unlink($list[0]);
                }
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/VenueZone/' . $venueZone->id)) {
                    mkdir(APP_PATH . '/media/VenueZone/' . $venueZone->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['image'];
                $dest = APP_PATH . '/media/VenueZone/' . $venueZone->id . '/' . $args['image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'VenueZone/' . $venueZone->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $venueZone->id;
                $attachment->class = 'VenueZone';
                $attachment->save();
            } else {
                $result['data'] = $venueZone;
            }
            if ($venueZone['is_having_seat_selection'] == 1 && $venueZone['is_upload_svg'] == 0) {
                if ((!empty($args['svg_image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['svg_image']))) {
                    $venueZone->is_upload_svg = 1;
                    $venueZone->save();
                    //Removing and ree inserting new image
                    $img = Models\Attachment::where('foreign_id', $request->getAttribute('venueZoneId'))->where('class', 'VenueZone')->where('filename', 'LIKE', "%svg%")->first();
                    if (!empty($img)) {
                        if (file_exists(APP_PATH . '/media/VenueZone/' . $request->getAttribute('venueZoneId') . '/' . $img['filename'])) {
                            unlink(APP_PATH . '/media/VenueZone/' . $request->getAttribute('venueZoneId') . '/' . $img['filename']);
                            $img->delete();
                        }
                    }
                    // Removing Thumb folder images
                    $mediadir = APP_PATH . '/client/app/images/';
                    $whitelist = array(
                        '127.0.0.1',
                        '::1'
                    );
                    if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                        $mediadir = APP_PATH . '/client/images/';
                    }
                    foreach (VENUE_ZONE_THUMB_SIZES as $key => $value) {
                        $list = glob($mediadir . $key . '/' . 'VenueZone' . '/' . $request->getAttribute('venueZoneId') . '.*');
                        @unlink($list[0]);
                    }
                    $attachment = new Models\Attachment;
                    if (!file_exists(APP_PATH . '/media/VenueZone/' . $venueZone->id)) {
                        mkdir(APP_PATH . '/media/VenueZone/' . $venueZone->id, 0777, true);
                    }
                    $src = APP_PATH . '/media/tmp/' . $args['svg_image'];
                    $dest = APP_PATH . '/media/VenueZone/' . $venueZone->id . '/' . $args['svg_image'];
                    copy($src, $dest);
                    unlink($src);
                    list($width, $height) = getimagesize($dest);
                    $attachment->filename = $args['svg_image'];
                    if (!empty($width)) {
                        $attachment->width = $width;
                        $attachment->height = $height;
                    }
                    $attachment->dir = 'VenueZone/' . $venueZone->id;
                    $attachment->amazon_s3_thumb_url = '';
                    $attachment->foreign_id = $venueZone->id;
                    $attachment->class = 'VenueZone';
                    $attachment->save();
                    $file_path = APP_PATH . '/media/VenueZone/' . $venueZone->id . '/' . $args['svg_image'];
                    // parse svg file and get section, row and seat and update in table process
                    $response = file_get_contents($file_path);
                    $dom = new DOMDocument();
                    $dom->loadXML($response);
                    $xpath = new DOMXPath($dom);
                    $rootNamespace = $dom->lookupNamespaceUri($dom->namespaceURI);
                    $xpath->registerNamespace('svg', $rootNamespace);
                    $sectionsLength = $xpath->query('//svg:g[@class="js-seat"]/@id')->length;
                    for ($i = 0; $i <= $sectionsLength; $i++) {
                        $venueZoneSection = new Models\VenueZoneSection;
                        $venueZoneSectionRow = new Models\VenueZoneSectionRow;
                        $venueZoneSectionSeat = new Models\VenueZoneSectionSeat;
                        $venueZoneData = $xpath->query('//svg:g[@class="js-seat"]/@id')->item($i)->nodeValue;
                        if ($venueZoneData != "") {
                            $data = explode('-', $venueZoneData);
                            $venueSecName = $data[0];
                            //Section save process
                            $venueZoneSecId = checkAlreadyVenueSecExists($venueSecName, $venueZone->id, $args['venue_id']);
                            if (!empty($venueZoneSecId) && $venueZoneSecId != '') {
                                $venueZoneSectionId = $venueZoneSecId;
                            } else {
                                $venueZoneSection->name = $venueSecName;
                                $venueZoneSection->venue_zone_id = $venueZone->id;
                                $venueZoneSection->venue_id = $args['venue_id'];
                                $venueZoneSection->seat_count = 0;
                                $venueZoneSection->save();
                                $venueZoneSectionId = $venueZoneSection->id;
                            }
                            // Venue zone section row save process
                            $venueRowId = $data[1];
                            $venueZoneSecRowId = checkAlreadyVenueSecRowExists($venueRowId, $venueZoneSectionId);
                            if (!empty($venueZoneSecRowId) && $venueZoneSecRowId != '') {
                                $venueZoneSectionRowId = $venueZoneSecRowId;
                            } else {
                                $venueZoneSectionRow->name = $venueRowId;
                                $venueZoneSectionRow->venue_zone_section_id = $venueZoneSectionId;
                                $venueZoneSectionRow->seat_count = 0;
                                $venueZoneSectionRow->save();
                                $venueZoneSectionRowId = $venueZoneSectionRow->id;
                            }
                            // Venue zone section row seat save process
                            $venueSeatId = $data[2];
                            $seatData = array(
                                'seat_id' => $venueSeatId,
                                'venue_section_row_id' => $venueZoneSectionRowId,
                                'venue_section_id' => $venueZoneSectionId,
                                'venue_id' => $args['venue_id'],
                                'venue_zone_id' => $venueZone->id
                            );
                            $venue_zone_sec_row_seat_id = checkAlreadyVenueSecRowSeatExists($seatData);
                            if (!empty($venue_zone_sec_row_seat_id)) {
                                $venueZoneSectionRowSeatId = $venue_zone_sec_row_seat_id;
                            } else {
                                $venueZoneSectionSeat->venue_id = $args['venue_id'];
                                $venueZoneSectionSeat->venue_zone_id = $venueZone->id;
                                $venueZoneSectionSeat->venue_zone_section_id = $venueZoneSectionId;
                                $venueZoneSectionSeat->venue_zone_section_row_id = $venueZoneSectionRowId;
                                $venueZoneSectionSeat->seat_number = $venueSeatId;
                                $venueZoneSectionSeat->seat_information = $args['name'] . '_seat';
                                $venueZoneSectionSeat->x_position = 0.00;
                                $venueZoneSectionSeat->y_position = 0.00;
                                $venueZoneSectionSeat->is_seat = true;
                                $venueZoneSectionSeat->is_box = false;
                                $venueZoneSectionSeat->box_number = 'null';
                                $venueZoneSectionSeat->save();
                                $venueZoneSectionRowSeatId = $venueZoneSectionSeat->id;
                                $total_seats++;
                            }
                            $id = $venueZoneSectionRowSeatId;
                            $xpath->query('//svg:g[@class="js-seat"]/@id')->item($i)->nodeValue = 'seat-' . $id;
                        }
                    }
                    $whitelist = array(
                        '127.0.0.1',
                        '::1'
                    );
                    if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                        if (!file_exists(APP_PATH . '/client/images/VenueZoneSVG/' . $venueZone->id)) {
                            mkdir(APP_PATH . '/client/images/VenueZoneSVG/' . $venueZone->id, 0777, true);
                        }
                        file_put_contents(APP_PATH . '/client/images/VenueZoneSVG/' . $venueZone->id . '/' . $args['svg_image'], $dom->saveXML());
                        $venueZone->seat_count = $total_seats;
                    } else {
                        if (!file_exists(APP_PATH . '/client/app/images/VenueZoneSVG/' . $venueZone->id)) {
                            mkdir(APP_PATH . '/client/app/images/VenueZoneSVG/' . $venueZone->id, 0777, true);
                        }
                        file_put_contents(APP_PATH . '/client/app/images/VenueZoneSVG/' . $venueZone->id . '/' . $args['svg_image'], $dom->saveXML());
                        $venueZone->seat_count = $total_seats;
                    }
                    // svg process
                    
                } else {
                    $result['data'] = $venueZone;
                }
            }
            $venueZone->save();
            $venue_zone = $venueZone->with('attachments')->where('id', $venueZone->id)->get();
            $result['data'] = $venue_zone->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Venue zone could not be updated. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Venue zone could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateVenueZone'));
$app->GET('/api/v1/venue_zones', function ($request, $response, $args)
{
    if (!empty($_GET['token'])) {
        $authUser = getUserDetails($_GET['token']);
    }
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $venueZone = Models\VenueZone::with('attachments', 'venue', 'venue_zone_sections', 'venue_zone_section_row', 'venue_zone_section_seats');
        if(!empty($authUser['role_id'] == \Constants\ConstUserTypes::EventOrganizer))
        {
            $event = Models\Event::where('user_id',$authUser['id'])->select('id','venue_id')->get()->toArray();
            if(!empty($event))
            {
            foreach($event as $values)
            {
               $venueValue[] =  $values['venue_id'];       
            }
              $venueZone->whereIn('venue_id',$venueValue);
            }      
        }
        $venueZone = $venueZone->Filter($queryParams)->paginate(20)->toArray();
        $data = $venueZone['data'];
        unset($venueZone['data']);
        if ($queryParams[limit] == 'all') {
            $venueZone = Models\VenueZone::all()->toArray();
        }
        if (!empty($queryParams['venue_id'])) {
            $venue = explode(',', $queryParams['venue_id']);
            $venueZone = Models\Venue::with('venue_zone')->whereIn('id', $venue)->paginate()->toArray();
            $data = $venueZone['data'];
            unset($venueZone['data']);
        }
        $results = array(
            'data' => $data,
            '_metadata' => $venueZone
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListVenueZone'));

$app->GET('/api/v1/venue_zone_sections', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
            $venueZoneSection['data'] = Models\VenueZoneSection::Filter($queryParams)->get()->toArray();    
        } else {
            $count = PAGE_LIMIT;
            if(!empty($queryParams['limit'])) {
                $count = $queryParams['limit'];
            }
            $venueZoneSection = Models\VenueZoneSection::Filter($queryParams)->paginate($count)->toArray();
        }
        $data = $venueZoneSection['data'];
        unset($venueZoneSection['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $venueZoneSection
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListVenueZoneSection'));

$app->GET('/api/v1/venue_zone_section_rows', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
            $venueZoneSectionRow['data'] = Models\VenueZoneSectionRow::Filter($queryParams)->get()->toArray();    
        } else {
            $count = PAGE_LIMIT;
            if(!empty($queryParams['limit'])) {
                $count = $queryParams['limit'];
            }
            $venueZoneSectionRow = Models\VenueZoneSectionRow::Filter($queryParams)->paginate($count)->toArray();
        }
        $data = $venueZoneSectionRow['data'];
        unset($venueZoneSectionRow['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $venueZoneSectionRow
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListVenueZoneSectionRow'));

$app->GET('/api/v1/venue_zone_section_seats', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
            $venueZoneSectionSeat['data'] = Models\VenueZoneSectionSeat::Filter($queryParams)->get()->toArray();    
        } else {
            $count = PAGE_LIMIT;
            if(!empty($queryParams['limit'])) {
                $count = $queryParams['limit'];
            }
            $venueZoneSectionSeat = Models\VenueZoneSectionSeat::Filter($queryParams)->paginate($count)->toArray();
        }
        $data = $venueZoneSectionSeat['data'];
        unset($venueZoneSectionSeat['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $venueZoneSectionSeat
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListVenueZoneSectionRowSeat'));
/**
 * GET venuesGet
 * Summary: Fetch all Venues
 * Notes: Returns all Venues from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/venues', function ($request, $response, $args)
{
     if (!empty($_GET['token'])) {
        $authUser = getUserDetails($_GET['token']);
    }
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $venues =Models\Venue::with('attachments', 'venue_service','city','state','country');
         if(!empty($authUser['role_id'] == \Constants\ConstUserTypes::EventOrganizer))
        {
            $events = Models\Event::where('user_id',$authUser['id'])->select('id','venue_id')->get()->toArray();
            foreach($events as $values)
            {
               $venue[] =  $values['venue_id'];             
            }
              $venues->whereIn('id',$venue);
        }
        $venues = $venues->Filter($queryParams)->paginate(20)->toArray();
        $data = $venues['data'];
        unset($venues['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $venues
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * POST venuesPost
 * Summary: Creates a new Venue
 * Notes: Creates a new Venue
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/venues', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $venue = new Models\Venue;
    foreach ($args as $key => $arg) {
        if ($key != 'image' && $key != 'svg_image' && $key != 'slider_image') {
            $venue->{$key} = $arg;
        }
    }
    $venue->slug = Inflector::slug(strtolower($venue->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $venue->validate($args);
        if (empty($validationErrorFields)) {
            $venue->save();
            if ((!empty($args['image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['image']))) {
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/Venue/' . $venue->id)) {
                    mkdir(APP_PATH . '/media/Venue/' . $venue->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['image'];
                $dest = APP_PATH . '/media/Venue/' . $venue->id . '/' . $args['image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'Venue/' . $venue->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $venue->id;
                $attachment->class = 'Venue';
                $attachment->save();
            }
            if ((!empty($args['slider_image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['slider_image']))) {
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/VenueSlider/' . $venue->id)) {
                    mkdir(APP_PATH . '/media/VenueSlider/' . $venue->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['slider_image'];
                $dest = APP_PATH . '/media/VenueSlider/' . $venue->id . '/' . $args['slider_image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['slider_image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'VenueSlider/' . $venue->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $venue->id;
                $attachment->class = 'VenueSlider';
                $attachment->save();
            }
            if ((!empty($args['svg_image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['svg_image']))) {
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/Venue/' . $venue->id)) {
                    mkdir(APP_PATH . '/media/Venue/' . $venue->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['svg_image'];
                $dest = APP_PATH . '/media/Venue/' . $venue->id . '/' . $args['svg_image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['svg_image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'Venue/' . $venue->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $venue->id;
                $attachment->class = 'Venue';
                $attachment->save();
            }
            $file_path = APP_PATH . '/media/Venue/' . $venue->id . '/' . $args['svg_image'];
            //Parse svg file and update in venue zone table process
            $response = file_get_contents($file_path);
            $dom = new DOMDocument();
            $dom->loadXML($response);
            $xpath = new DOMXPath($dom);
            $rootNamespace = $dom->lookupNamespaceUri($dom->namespaceURI);
            $xpath->registerNamespace('svg', $rootNamespace);
            $sectionsLength = $xpath->query('//svg:g[@class="js-zone"]/@id')->length;
            for ($i = 0; $i <= $sectionsLength; $i++) {
                $venueZone = new Models\VenueZone;
                $venueZoneData = $xpath->query('//svg:g[@class="js-zone"]/@id')->item($i)->nodeValue;
                if ($venueZoneData != "") {
                    $venueZone->name = $venueZoneData;
                    $venueZone->venue_id = $venue->id;
                    $venueZone->seat_count = 0;
                    $venueZone->is_having_seat_selection = 0;
                    $venueZone->save();
                    $xpath->query('//svg:g[@class="js-zone"]/@id')->item($i)->nodeValue = 'zone-' . $venueZone->id;
                }
            }
            $whitelist = array(
                '127.0.0.1',
                '::1'
            );
            if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                if (!file_exists(APP_PATH . '/client/images/VenueSVG/' . $venue->id)) {
                    mkdir(APP_PATH . '/client/images/VenueSVG/' . $venue->id, 0777, true);
                }
                file_put_contents(APP_PATH . '/client/images/VenueSVG/' . $venue->id . '/' . $args['svg_image'], $dom->saveXML());
            } else {
                if (!file_exists(APP_PATH . '/client/app/images/VenueSVG/' . $venue->id)) {
                    mkdir(APP_PATH . '/client/app/images/VenueSVG/' . $venue->id, 0777, true);
                }
                file_put_contents(APP_PATH . '/client/app/images/VenueSVG/' . $venue->id . '/' . $args['svg_image'], $dom->saveXML());
            }
            // svg process
            $venue = Models\Venue::with('attachments')->find($venue->id);
            $result['data'] = $venue->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Venue could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Venue could not be added. Please, try again.', '', 1);
    }
});
/**
 * DELETE venuesVenueIdDelete
 * Summary: Delete Venue
 * Notes: Deletes a single Venue based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/venues/{venueId}', function ($request, $response, $args)
{
    $venue = Models\Venue::find($request->getAttribute('venueId'));
    try {
        $venue->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Venue could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteVenue'));
/**
 * GET venuesVenueIdGet
 * Summary: Fetch Venue
 * Notes: Returns a Venue based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/venues/{venueId}', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $venue = Models\Venue::with('attachments', 'venue_service')->find($request->getAttribute('venueId'));
    $result = array();
    if (!empty($queryParams['filter'])) {
        $event = Models\Event::where('venue_id', $request->getAttribute('venueId'))->get()->toArray();
        $i = 0;
        foreach ($event as $key => $value) {
            $event_id[$i] = $value['id'];
            $i++;
        }
        if (!empty($event_id)) {
            if ($queryParams['filter'] == 'upcoming') {
                $eventSchedule = Models\EventSchedule::whereIn('event_id', $event_id)->whereDate('start_date', '>=', date("Y-m-d"))->select('event_id')->get()->toArray();
                $j = 0;
                foreach ($eventSchedule as $key1 => $value1) {
                    $event_ids[$i] = $value1['event_id'];
                    $j++;
                }
                $events = Models\Event::with('attachments', 'attachment_floor_plan', 'attachment_ticket_price')->whereIn('id', $event_ids)->get();
            }
            if ($queryParams['filter'] == 'past') {
                $eventSchedule = Models\EventSchedule::whereIn('event_id', $event_id)->whereDate('start_date', '<=', date("Y-m-d"))->select('event_id')->get()->toArray();
                $j = 0;
                foreach ($eventSchedule as $key1 => $value1) {
                    $event_ids[$i] = $value1['event_id'];
                    $j++;
                }
                $events = Models\Event::with('attachments', 'attachment_floor_plan', 'attachment_ticket_price')->whereIn('id', $event_ids)->get();
            }
        }
    }
    if (!empty($venue)) {
        if (!empty($events)) {
            $Events['events'] = $events->toArray();
            $Venue = $venue->toArray();
            $venue = array_merge($Events, $Venue);
            $result['data'] = $venue;
            return renderWithJson($result);
        } else {
            $result['data'] = $venue->toArray();
            return renderWithJson($result);
        }
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
/**
 * PUT venuesVenueIdPut
 * Summary: Update Venue by its id
 * Notes: Update Venue by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/venues/{venueId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $venue = Models\Venue::find($request->getAttribute('venueId'));
    foreach ($args as $key => $arg) {
        if (!is_object($arg) && !is_array($arg)) {
            if ($key != 'image' && $key != 'svg_image' && $key != 'slider_image' && $key != 'venue_service') {
                $venue->{$key} = $arg;
            }
        }
    }
    $venue->slug = Inflector::slug(strtolower($venue->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $venue->validate($args);
        if (empty($validationErrorFields)) {
            $venue->save();
            if ((!empty($args['image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['image']))) {
                //Removing and ree inserting new image
                $img = Models\Attachment::where('foreign_id', $request->getAttribute('venueId'))->where('class', 'Venue')->first();
                if (!empty($img)) {
                    if (file_exists(APP_PATH . '/media/Venue/' . $request->getAttribute('venueId') . '/' . $img['filename'])) {
                        unlink(APP_PATH . '/media/Venue/' . $request->getAttribute('venueId') . '/' . $img['filename']);
                        $img->delete();
                    }
                }
                // Removing Thumb folder images
                $mediadir = APP_PATH . '/client/app/images/';
                $whitelist = array(
                    '127.0.0.1',
                    '::1'
                );
                if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                    $mediadir = APP_PATH . '/client/images/';
                }
                foreach (VENUE_THUMB_SIZES as $key => $value) {
                    $list = glob($mediadir . $key . '/' . 'Venue' . '/' . $request->getAttribute('venueId') . '.*');
                    @unlink($list[0]);
                }
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/Venue/' . $venue->id)) {
                    mkdir(APP_PATH . '/media/Venue/' . $venue->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['image'];
                $dest = APP_PATH . '/media/Venue/' . $venue->id . '/' . $args['image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'Venue/' . $venue->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $venue->id;
                $attachment->class = 'Venue';
                $attachment->save();
            }
             if ((!empty($args['svg_image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['svg_image']))) {
                //Removing and ree inserting new image
                    $img = Models\Attachment::where('foreign_id', $venue->id)->where('class', 'Venue')->where('filename', 'LIKE', "%svg%")->first();
                    if (!empty($img)) {
                        if (file_exists(APP_PATH . '/media/Venue/' . $venue->id . '/' . $img['filename'])) {
                            unlink(APP_PATH . '/media/Venue/' . $venue->id . '/' . $img['filename']);
                            $img->delete();
                        }
                    }
                    // Removing Thumb folder images
                    $mediadir = APP_PATH . '/client/app/images/';
                    $whitelist = array(
                        '127.0.0.1',
                        '::1'
                    );
                    if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                        $mediadir = APP_PATH . '/client/images/';
                    }
                    foreach (THUMB_SIZES as $key => $value) {
                        $list = glob($mediadir . $key . '/' . 'Venue' . '/' .  $venue->id . '.*');
                        @unlink($list[0]);
                    } 
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/Venue/' . $venue->id)) {
                    mkdir(APP_PATH . '/media/Venue/' . $venue->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['svg_image'];
                $dest = APP_PATH . '/media/Venue/' . $venue->id . '/' . $args['svg_image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['svg_image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'Venue/' . $venue->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $venue->id;
                $attachment->class = 'Venue';
                $attachment->save();
            
            $file_path = APP_PATH . '/media/Venue/' . $venue->id . '/' . $args['svg_image'];
            //Parse svg file and update in venue zone table process
            $response = file_get_contents($file_path);
            $dom = new DOMDocument();
            $dom->loadXML($response);
            $xpath = new DOMXPath($dom);
            $rootNamespace = $dom->lookupNamespaceUri($dom->namespaceURI);
            $xpath->registerNamespace('svg', $rootNamespace);
            $sectionsLength = $xpath->query('//svg:g[@class="js-zone"]/@id')->length;
            for ($i = 0; $i <= $sectionsLength; $i++) {                
                $venueZoneData = $xpath->query('//svg:g[@class="js-zone"]/@id')->item($i)->nodeValue;
                if ($venueZoneData != "") {
                $venueZone = Models\VenueZone::where('name', $venueZoneData)->where('venue_id',  $venue->id)->first();
                    if (!empty($venueZone)) {
                    
                    }else{
                        $venueZone = new Models\VenueZone;
                        $venueZone->seat_count = 0;
                        $venueZone->is_having_seat_selection = 0;
                    }                
                    $venueZone->name = $venueZoneData;
                    $venueZone->venue_id = $venue->id;                    
                    $venueZone->save();
                    $xpath->query('//svg:g[@class="js-zone"]/@id')->item($i)->nodeValue = 'zone-' . $venueZone->id;
                }
            }
            $whitelist = array(
                '127.0.0.1',
                '::1'
            );
            if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                if (!file_exists(APP_PATH . '/client/images/VenueSVG/' . $venue->id)) {
                    mkdir(APP_PATH . '/client/images/VenueSVG/' . $venue->id, 0777, true);
                }
                file_put_contents(APP_PATH . '/client/images/VenueSVG/' . $venue->id . '/' . $args['svg_image'], $dom->saveXML());
            } else {
                if (!file_exists(APP_PATH . '/client/app/images/VenueSVG/' . $venue->id)) {
                    mkdir(APP_PATH . '/client/app/images/VenueSVG/' . $venue->id, 0777, true);
                }
                file_put_contents(APP_PATH . '/client/app/images/VenueSVG/' . $venue->id . '/' . $args['svg_image'], $dom->saveXML());
              }
            }

            if ((!empty($args['slider_image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['slider_image']))) {
                //Removing and ree inserting new image
                $img = Models\Attachment::where('foreign_id', $request->getAttribute('venueId'))->where('class', 'VenueSlider')->first();
                if (!empty($img)) {
                    if (file_exists(APP_PATH . '/media/VenueSlider/' . $request->getAttribute('venueId') . '/' . $img['filename'])) {
                        unlink(APP_PATH . '/media/VenueSlider/' . $request->getAttribute('venueId') . '/' . $img['filename']);
                        $img->delete();
                    }
                }
                // Removing Thumb folder images
                $mediadir = APP_PATH . '/client/app/images/';
                $whitelist = array(
                    '127.0.0.1',
                    '::1'
                );
                if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                    $mediadir = APP_PATH . '/client/images/';
                }
                foreach (VENUE_THUMB_SIZES as $key => $value) {
                    $list = glob($mediadir . $key . '/' . 'VenueSlider' . '/' . $request->getAttribute('venueId') . '.*');
                    @unlink($list[0]);
                }
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/VenueSlider/' . $venue->id)) {
                    mkdir(APP_PATH . '/media/VenueSlider/' . $venue->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['slider_image'];
                $dest = APP_PATH . '/media/VenueSlider/' . $venue->id . '/' . $args['slider_image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['slider_image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'VenueSlider/' . $venue->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $venue->id;
                $attachment->class = 'VenueSlider';
                $attachment->save();
            }
            $venue = Models\Venue::with('attachments')->find($venue->id);
            $result['data'] = $venue->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Venue could not be updated. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Venue could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateVenue'));
/**
 * GET newslettersGet
 * Summary: Fetch all Newsletters
 * Notes: Returns all Newsletters from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/newsletters', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $newsletters = Models\Newsletter::Filter($queryParams)->paginate(20)->toArray();
        $data = $newsletters['data'];
        unset($newsletters['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $newsletters
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListNewsletter'));
/**
 * DELETE newslettersNewsletterIdDelete
 * Summary: Delete Newsletter
 * Notes: Deletes a single Newsletter based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/newsletters/{newsletterId}', function ($request, $response, $args)
{
    $newsletter = Models\Newsletter::find($request->getAttribute('newsletterId'));
    try {
        $newsletter->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Newsletter could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteNewsletter'));
/**
 * POST newslettersPost
 * Summary: Creates a new Newsletter
 * Notes: Creates a new Newsletter
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/newsletters', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $newsletter = new Models\Newsletter;
    foreach ($args as $key => $arg) {
        $newsletter->{$key} = $arg;
    }
    $result = array();
    try {
        $validationErrorFields = $newsletter->validate($args);
        if (empty($validationErrorFields)) {
            $newsletter->save();
            $result['data'] = $newsletter->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Newsletter could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Newsletter could not be added. Please, try again.', '', 1);
    }
});
$app->GET('/api/v1/newsletters/{newsletterId}', function ($request, $response, $args)
{
    $newsletter = Models\Newsletter::find($request->getAttribute('newsletterId'));
    $result = array();
    if (!empty($newsletter)) {
        $result['data'] = $newsletter->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewNewsLetter'));
/**
 * GET VenueZonePreview
 * Summary: Fetch all VenueZonePreview
 * Notes: Returns all VenueZonePreview from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/venues/{venueId}/venue_zone_previews', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $Venuezonepreview = Models\VenueZonePreview::with('attachments', 'venue_zone_section_seats')->Filter($queryParams)->paginate(20)->toArray();
        $data = $Venuezonepreview['data'];
        unset($Venuezonepreview['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $Venuezonepreview
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
$app->GET('/api/v1/venue_zone_previews', function ($request, $response, $args)
{
     if (!empty($_GET['token'])) {
        $authUser = getUserDetails($_GET['token']);
    }
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $venuezonepreview = Models\VenueZonePreview::with('attachments', 'venue_zone_section_seats');
          if(!empty($authUser['role_id'] == \Constants\ConstUserTypes::EventOrganizer))
        {
            $events = Models\Event::where('user_id',$authUser['id'])->select('id','venue_id')->get()->toArray();
            if(!empty($events))
            {
            foreach($events as $values)
            {
               $venue[] =  $values['venue_id'];             
            }
              $venuezonepreview->whereIn('venue_id',$venue);
            }
           
        }
        $Venuezonepreview = $venuezonepreview->Filter($queryParams)->paginate(20)->toArray();
        $data = $Venuezonepreview['data'];
        unset($Venuezonepreview['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $Venuezonepreview
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * POST VenueZonePreview
 * Summary: Creates a new VenueZonePreview
 * Notes: Creates a new VenueZonePreview
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/venues/{venueId}/venue_zone_previews', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $Venuezonepreview = new Models\VenueZonePreview;
    foreach ($args as $key => $arg) {
        if ($key != 'image') {
            $Venuezonepreview->{$key} = $arg;
            $Venuezonepreview->venue_id = $request->getAttribute('venueId');
        }
    }
    $result = array();
    try {
        $validationErrorFields = $Venuezonepreview->validate($args);
        if (empty($validationErrorFields)) {
            $Venuezonepreview->save();
            if ((!empty($args['image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['image']))) {
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/VenueZonePreview/' . $Venuezonepreview->id)) {
                    mkdir(APP_PATH . '/media/VenueZonePreview/' . $Venuezonepreview->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['image'];
                $dest = APP_PATH . '/media/VenueZonePreview/' . $Venuezonepreview->id . '/' . $args['image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'VenueZonePreview/' . $Venuezonepreview->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $Venuezonepreview->id;
                $attachment->class = 'VenueZonePreview';
                $attachment->save();
            } else {
                $result['data'] = $Venuezonepreview;
            }
            $VenueZonePreview = Models\VenueZonePreview::with('attachments')->find($Venuezonepreview->id);
            $result['data'] = $VenueZonePreview->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'News could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Venuezonepreview could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('CanCreateVenueZonePreview'));
/**
 * DELETE VenueZonePreview
 * Summary: Delete VenueZonePreview
 * Notes: Deletes a single VenueZonePreview based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/venues/{venueId}/venue_zone_previews/{venueZonePreviewId}', function ($request, $response, $args)
{
    $Venuezonepreview = Models\VenueZonePreview::find($request->getAttribute('venueZonePreviewId'));
    try {
        $Venuezonepreview->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Venuezonepreview could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('CanDeleteVenueZonePreview'));
/**
 * GET VenueZonePreview
 * Summary: Fetch VenueZonePreview
 * Notes: Returns a VenueZonePreview based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/venues/{venueId}/venue_zone_previews/{venueZonePreviewId}', function ($request, $response, $args)
{
    $Venuezonepreview = Models\VenueZonePreview::with('attachments', 'venue_zone_section_seats')
        ->where('id', $request->getAttribute('venueZonePreviewId'))
        ->where('venue_id', $request->getAttribute('venueId'))->first();
    $result = array();
    if (!empty($Venuezonepreview)) {
        $result['data'] = $Venuezonepreview;
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
/**
 * PUT VenueZonePreview
 * Summary: Update VenueZonePreview by its id
 * Notes: Update VenueZonePreview by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/venues/{venueId}/venue_zone_previews/{venueZonePreviewId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $VenueZonePreview = Models\VenueZonePreview::find($request->getAttribute('venueZonePreviewId'));
    foreach ($args as $key => $arg) {
        if ($key != 'image' && $key != 'attachments' && $key != 'venue_zone_section_seats') {
            $VenueZonePreview->{$key} = $arg;
        }
    }
    $result = array();
    try {
        $validationErrorFields = $VenueZonePreview->validate($args);
        if (empty($validationErrorFields)) {
            $VenueZonePreview->save();
            if ((!empty($args['image'])) && (file_exists(APP_PATH . '/media/tmp/' . $args['image']))) {
                //Removing and ree inserting new image
                $img = Models\Attachment::where('foreign_id', $request->getAttribute('venueZonePreviewId'))->where('class', 'VenueZonePreview')->first();
                if (!empty($img)) {
                    if (file_exists(APP_PATH . '/media/VenueZonePreview/' . $request->getAttribute('venueZonePreviewId') . '/' . $img['filename'])) {
                        unlink(APP_PATH . '/media/VenueZonePreview/' . $request->getAttribute('venueZonePreviewId') . '/' . $img['filename']);
                        $img->delete();
                    }
                }
                // Removing Thumb folder images
                $mediadir = APP_PATH . '/client/app/images/';
                $whitelist = array(
                    '127.0.0.1',
                    '::1'
                );
                if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                    $mediadir = APP_PATH . '/client/images/';
                }
                foreach (VENUE_ZONE_PREVIEW_THUMB_SIZES as $key => $value) {
                    $list = glob($mediadir . $key . '/' . 'VenueZonePreview' . '/' . $request->getAttribute('venueZonePreviewId') . '.*');
                    @unlink($list[0]);
                }
                $attachment = new Models\Attachment;
                if (!file_exists(APP_PATH . '/media/VenueZonePreview/' . $Venuezonepreview->id)) {
                    mkdir(APP_PATH . '/media/VenueZonePreview/' . $Venuezonepreview->id, 0777, true);
                }
                $src = APP_PATH . '/media/tmp/' . $args['image'];
                $dest = APP_PATH . '/media/VenueZonePreview/' . $Venuezonepreview->id . '/' . $args['image'];
                copy($src, $dest);
                unlink($src);
                list($width, $height) = getimagesize($dest);
                $attachment->filename = $args['image'];
                if (!empty($width)) {
                    $attachment->width = $width;
                    $attachment->height = $height;
                }
                $attachment->dir = 'VenueZonePreview/' . $Venuezonepreview->id;
                $attachment->amazon_s3_thumb_url = '';
                $attachment->foreign_id = $VenueZonePreview->id;
                $attachment->class = 'VenueZonePreview';
                $attachment->save();
            } else {
                $result['data'] = $VenueZonePreview;
            }
            $VenueZonePreview = Models\VenueZonePreview::with('attachments')->find($VenueZonePreview->id);
            $result = $VenueZonePreview->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Venuezonepreview could not be updated. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Venuezonepreview could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('CanUpdateVenueZonePreview'));
/**
 * GET ContactscontactIdGet
 * Summary: get particular contact details
 * Notes: get particular contact details
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/orders/{orderId}', function ($request, $response, $args)
{
    $result = array();
    $order = Models\Order::with('delivery_methods', 'order_items','credit_card')->find($request->getAttribute('orderId'));
    $hash = 'download'.$request->getAttribute('orderId');
    $order['download_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/download/' .$request->getAttribute('orderId') . '/' . md5($hash);
     if (!empty($order)) {
        $result['data'] = $order->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewOrder'));
$app->GET('/api/v1/orders', function ($request, $response, $args)
{
    global $authUser;
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        if (!empty($queryParams['user_id'])) {
            $orders = Models\Order::with('delivery_methods', 'order_items','credit_card')->where('user_id', $queryParams['user_id'])->paginate(20)->toArray();
        } elseif ($authUser->role_id == \Constants\ConstUserTypes::EventOrganizer) {
            $eventOrganizerId = $authUser->id; 
            $events = Models\Event::where('user_id',$eventOrganizerId)->get()->toArray();
            if(!empty($events)) {
                foreach($events as $value) {
                    $eventId[] = $value['id'];
                }
                $orders = Models\Order::with('delivery_methods', 'order_items','credit_card')->whereIn('event_id', $eventId)->paginate(20)->toArray();
            } else {
               return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1); 
            }
        } else {
            if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
                $orders['data'] = Models\Order::with('delivery_methods', 'order_items','credit_card')->Filter($queryParams)->get()->toArray();
            } else {
                $count = PAGE_LIMIT;
                if(!empty($queryParams['limit'])) {
                    $count = $queryParams['limit'];
                }
                $orders = Models\Order::with('delivery_methods', 'order_items','credit_card')->Filter($queryParams)->paginate($count)->toArray(); 
            }          
        }        
        $i=0;
        foreach($orders['data'] as $order) {
            $order_data[$i]  = $order;
            $hash = 'download'.$order['id'];
            $order_data[$i]['download_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/download/' .$order['id'] . '/' . md5($hash);
            $i++;           
        } 
        $data = !empty($order_data) ? $order_data : array();
        unset($orders['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $orders
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListOrder'));

$app->GET('/api/v1/send_tickets/{orderId}', function ($request, $response, $args)
{
   $order_id =  $request->getAttribute('orderId');
   $payment = new Models\Payment;
   $payment->OrderEmailSend($order_id);
   $result = array(
        'status' => 'success'
    );
    return renderWithJson($result);
})->add(new ACL('canSendTicket'));
/**
 * POST Orders
 * Summary: Creates a new Orders
 * Notes: Creates a new Orders
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/orders/{session_id}', function ($request, $response, $args)
{       
    $args = $request->getParsedBody();    
    $session_id = $request->getAttribute('session_id');
    $payment = new Models\Payment;
    $payment_response = $payment->payment_process($session_id, $args);
    return renderWithJson($payment_response);
})->add(new ACL('canCreateOrder'));
/**
 * GET NewsCategory
 * Summary: Fetch NewsCategory
 * Notes: Returns a NewsCategory based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/news_categories/{newsCategoryId}', function ($request, $response, $args)
{
    $newsCategory = Models\NewsCategory::find($request->getAttribute('newsCategoryId'));
    $result = array();
    if (!empty($newsCategory)) {
        $result['data'] = $newsCategory->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
/**
 * PUT NewsCategory
 * Summary: Update NewsCategory by its id
 * Notes: Update NewsCategory by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/news_categories/{newsCategoryId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $newsCategory = Models\NewsCategory::find($request->getAttribute('newsCategoryId'));
    foreach ($args as $key => $arg) {
        $newsCategory->{$key} = $arg;
    }
    $newsCategory->slug = Inflector::slug(strtolower($newsCategory->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $newsCategory->validate($args);
        if (empty($validationErrorFields)) {
            $newsCategory->save();
            $result = $newsCategory->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Newscategory could not be updated. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Newscategory could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('CanUpdateNewsCategory'));
/**
 * GET NewsCategory
 * Summary: Fetch all NewsCategory
 * Notes: Returns all NewsCategory from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/news_categories', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $newsCategories = Models\NewsCategory::Filter($queryParams)->paginate(20)->toArray();
        $data = $newsCategories['data'];
        unset($newsCategories['data']);
        if ($queryParams[filter] == 'all') {
            $data = Models\NewsCategory::all()->toArray();
        }
        $results = array(
            'data' => $data,
            '_metadata' => $newsCategories
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
/**
 * POST NewsCategory
 * Summary: Creates a new NewsCategory
 * Notes: Creates a new NewsCategory
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/news_categories', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $newsCategory = new Models\NewsCategory;
    foreach ($args as $key => $arg) {
        $newsCategory->{$key} = $arg;
    }
    $newsCategory->slug = Inflector::slug(strtolower($newsCategory->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $newsCategory->validate($args);
        if (empty($validationErrorFields)) {
            $newsCategory->save();
            $result['data'] = $newsCategory->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Newscategory could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Newscategory could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('CanCreateNewsCategory'));
$app->DELETE('/api/v1/news_categories/{newsCategoryId}', function ($request, $response, $args)
{
    $newsCategory = Models\NewsCategory::find($request->getAttribute('newsCategoryId'));
    try {
        $newsCategory->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'newsCategory could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('CanDeleteNewsCategory'));

/**
 * GET Lists
 * Summary: Fetch all List
 * Notes: Returns all list from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/lists', function ($request, $response, $args)
{
    global $authUser;
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        if($authUser->role_id != \Constants\ConstUserTypes::Admin) {
            $queryParams['user_id'] = $authUser->id; 
        }
        if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
            $lists['data'] = Models\Lists::with('guest_list')->Filter($queryParams)->get()->toArray();
        } else {
            $count = PAGE_LIMIT;
            if(!empty($queryParams['limit'])) {
                $count = $queryParams['limit'];
            }
            $lists = Models\Lists::with('guest_list')->Filter($queryParams)->paginate($count)->toArray();
        }
        $data = $lists['data'];
        unset($lists['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $lists
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('CanListLists'));
/**
 * POST List
 * Summary: Creates a new List
 * Notes: Creates a new List
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/lists', function ($request, $response, $args)
{
    global $authUser;
    $args = $request->getParsedBody();
    $list = new Models\Lists;
    foreach ($args as $key => $arg) {
        $list->{$key} = $arg;
    }
    $list->slug = Inflector::slug(strtolower($list->name) , '-');
    $result = array();
    try {
        $validationErrorFields = $list->validate($args);
        if (empty($validationErrorFields)) {
            $list->user_id = $authUser->id;
            $list->save();
            $result['data'] = $list->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'List could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'List could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('CanCreateList'));
/**
 * GET List
 * Summary: Fetch List
 * Notes: Returns a List based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/lists/{listId}', function ($request, $response, $args)
{
    global $authUser;
    $queryParams = $request->getQueryParams();
    if($authUser->role_id != \Constants\ConstUserTypes::Admin) {
        $queryParams['user_id'] = $authUser->id; 
    }
    $list = Models\Lists::Filter($queryParams)->where('id', $request->getAttribute('listId'))->first();
    $result = array();
    try {
        if (!empty($list)) {
            $result['data'] = $list->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'No record found', '', 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'List could not be found. Please, try again.', '', 1);
    }
})->add(new ACL('CanViewList'));
/**
 * PUT List
 * Summary: Update List by its id
 * Notes: Update List by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/lists/{listId}', function ($request, $response, $args)
{
    global $authUser;
    $args = $request->getParsedBody();
    $list = Models\Lists::find($request->getAttribute('listId'));
    if(!empty($list) && $list->user_id == $authUser->id) { 
        foreach ($args as $key => $arg) {
            if (!is_object($arg) && !is_array($arg)) {
                $list->{$key} = $arg;
            }    
        }
        $list->slug = Inflector::slug(strtolower($list->name) , '-');
        $result = array();
        try {
            $validationErrorFields = $list->validate($args);
            if (empty($validationErrorFields)) {
                $list->user_id = $authUser->id;
                $list->save();
                $result = $list->toArray();
                return renderWithJson($result);
            } else {
                return renderWithJson($result, 'List could not be updated. Please, try again.', $validationErrorFields, 1);
            }
        }
        catch(Exception $e) {
            return renderWithJson($result, 'List could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'List could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('CanUpdateList'));
/**
 * DELETE List
 * Summary: Delete List by its id
 * Notes: Delete List by its id
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/lists/{listId}', function ($request, $response, $args)
{
    global $authUser;
    $list = Models\Lists::find($request->getAttribute('listId'));
    $result = array();
    if(!empty($list) && $list->user_id == $authUser->id) {
        try {
            if($list->delete()) {
                Models\GuestsList::where('list_id', $request->getAttribute('listId'))->delete();
            }
            $result = array(
                'status' => 'success',
            );
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'list could not be deleted. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'list could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('CanDeleteList'));

/**
 * GET Guests
 * Summary: Fetch all Guest
 * Notes: Returns all Guests from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/guests', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
            $guests['data'] = Models\Guest::Filter($queryParams)->get()->toArray();
        } else {
            $count = PAGE_LIMIT;
            if(!empty($queryParams['limit'])) {
                $count = $queryParams['limit'];
            }
            $guests = Models\Guest::Filter($queryParams)->paginate($count)->toArray();
        }
        $data = $guests['data'];
        unset($guests['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $guests
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('CanListGuests'));
/**
 * POST Guest
 * Summary: Creates a new Guest
 * Notes: Creates a new Guest
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/guests', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $guest = new Models\Guest;
    $validationErrorFields = array();
    foreach ($args as $key => $arg) {
        if($key != 'list_id') {
            $guest->{$key} = $arg;
        }
    }
    $result = array();
    try {
        $validationErrorFields = $guest->validate($args);
        if(empty($args['list_id'])) {
            $validationErrorFields['list'] = 'Please select the list to add the guest';
        }
        if (empty($validationErrorFields)) {
            $guest->save();
            if(!empty($args['list_id'])) {
                foreach ($args['list_id'] as $list) {
                    $guests_list = new Models\GuestsList;
                    $guests_list->guest_id = $guest->id;
                    $guests_list->list_id = $list;
                    $guests_list->save();
                    $count = Models\GuestsList::where('list_id',$guests_list->list_id)->count();
                    $updateCount = Models\Lists::where('id',$guests_list->list_id)->update(['total_guest' => $count]);
                }
            }
            $result['data'] = $guest->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Guest could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Guest could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('CanCreateGuest'));
/**
 * GET Guest
 * Summary: Fetch Guest
 * Notes: Returns a Guest based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/guests/{guestId}', function ($request, $response, $args)
{
    $guest = Models\Guest::find($request->getAttribute('guestId'));
    $result = array();
    try {
        if (!empty($guest)) {
            $guest_lists = Models\GuestsList::where('guest_id',$request->getAttribute('guestId'))->select('list_id')->get()->toArray();            
            if(!empty($guest_lists)){
                foreach($guest_lists as $guest_list){
                    $list_ids[] = $guest_list['list_id'];
                }
            }
            $guest->list_id = $list_ids;
            $result['data'] = $guest->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'No record found', '', 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Guest could not be found. Please, try again.', '', 1);
    }
})->add(new ACL('CanViewGuest'));
/**
 * PUT guest
 * Summary: Update guest by its id
 * Notes: Update guest by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/guests/{guestId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $guest = Models\Guest::find($request->getAttribute('guestId'));
    foreach ($args as $key => $arg) {
        if($key != 'list_id') {
            $guest->{$key} = $arg;
        }
    }
    $result = array();
    try {
        $validationErrorFields = $guest->validate($args);
        if (empty($validationErrorFields)) {
            $guest->save();
            $guest_lists = Models\GuestsList::where('guest_id', $guest->id)->get()->toArray();
            if(!empty($guest_lists)) {
                foreach($guest_lists as $guest_list) {
                    $guest_list_id[] = $guest_list['id'];
                }
                if(empty($args['list_id'])) {                    
                     $list_ids = Models\GuestsList::whereIn('id', $guest_list_id)->select('list_id')->get()->toArray();
                    foreach($list_ids as $list_id){
                        $listIds[] = $list_id['list_id'];
                    }    
                    Models\Lists::whereIn('id',$listIds)->decrement('total_guest',1);
                    Models\GuestsList::whereIn('id', $guest_list_id)->delete(); 
                } else {
                    foreach ($args['list_id'] as $key => $value) {
                        if (!empty($value)) {
                            $args_guests_list_id[] = $value;
                        }
                    }
                    $ids_to_delete = array_diff($guest_list_id, $args_guests_list_id);  
                    $list_ids = Models\GuestsList::whereIn('id', $ids_to_delete)->select('list_id')->get()->toArray();
                    foreach($list_ids as $list_id){
                        $listIds[] = $list_id['list_id'];
                    }     
                    Models\Lists::whereIn('id',$listIds)->decrement('total_guest',1);    
                    Models\GuestsList::whereIn('id', $ids_to_delete)->delete();                        
                    foreach ($args['list_id'] as $key => $value) {                        
                        if (!empty($value)) {                            
                            $guests_list = Models\GuestsList::find($value);
                            if(empty($guests_list)){
                               $guests_list = new Models\GuestsList;                            
                            }
                            $guests_list->guest_id = $guest->id;
                            $guests_list->list_id = $value;
                            $guests_list->save();
                            $count = Models\GuestsList::where('list_id',$guests_list->list_id)->count();                          
                            $updateCount = Models\Lists::where('id',$guests_list->list_id)->update(['total_guest' => $count]);
                        }
                    }   
                }
            } else {
                if (!empty($args['list_id'])) {
                    foreach ($args['list_id'] as $key => $value) {
                        $guests_list = new Models\GuestsList;
                        $guests_list->guest_id = $guest->id;
                        $guests_list->list_id = $value['list_id'];
                        $guests_list->save();
                    }
                }
            }
            $result = $guest->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Guest could not be updated. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Guest could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('CanUpdateGuest'));
/**
 * DELETE Guest
 * Summary: Delete Guest by its id
 * Notes: Delete Guest by its id
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/guests/{guestId}', function ($request, $response, $args)
{
    $guest = Models\Guest::find($request->getAttribute('guestId'));
    if(!empty($guest)) {
        try {
            $list_ids = Models\GuestsList::where('guest_id',$request->getAttribute('guestId'))->select('list_id')->get()->toArray();;
                    foreach($list_ids as $list_id){
                        $listIds[] = $list_id['list_id'];
                    }     
                Models\Lists::whereIn('id',$listIds)->decrement('total_guest',1);               
            if($guest->delete()) {             
                Models\GuestsList::where('guest_id', $request->getAttribute('guestId'))->delete();
            }
            $result = array(
                'status' => 'success',
            );
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'guest could not be deleted. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'guest could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('CanDeleteGuest'));

/**
 * GET Guest
 * Summary: Fetch Guest
 * Notes: Returns a Guest based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/lists/{listId}/guests', function ($request, $response, $args)
{
    global $authUser;
    $list = Models\Lists::find($request->getAttribute('listId'));
    $result = array();
    if(empty($list) && $list->user_id != $authUser->id) {
        return renderWithJson($result, 'Guest could not be found. Please, try again.', '', 1);
    } else { 
        $guests = Models\GuestsList::with('guest', 'lists')->where('list_id', $list->id)->get();
        try {
            if (!empty($guests)) {
                $result['data'] = $guests->toArray();
                return renderWithJson($result);
            } else {
                return renderWithJson($result, 'No record found', '', 1);
            }
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Guest could not be found. Please, try again.', '', 1);
        }
    }
})->add(new ACL('CanListGuestLists'));

/**
 * POST SendInvitation
 * Summary: Creates a SendInvitation
 * Notes: Creates a SendInvitation
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/send_invitation', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $sendInvitation = new Models\SendInvitation;
    $validationErrorFields = array();
    foreach ($args as $key => $arg) {
        if($key != 'send_to_id') {
            $sendInvitation->{$key} = $arg;
        }
    }
    if(empty($args['send_to_id'])) {
        $validationErrorFields['send_to_id'] = "please enter the valid recipients";
    }
    $result = array();
    try {
        $validationErrorFields = $sendInvitation->validate($args);
        if (empty($validationErrorFields)) {
            if(!empty($sendInvitation->is_send_to_list) && !empty($args['send_to_id'])) {
                foreach($args['send_to_id'] as $send_to) {
                    $guests = Models\GuestsList::where('list_id', $send_to['id'])->get()->toArray();
                    foreach($guests as $guest) {
                        $send_invitation = new Models\SendInvitation;
                        $send_invitation->event_id = $sendInvitation->event_id;
                        $send_invitation->event_schedule_id = $sendInvitation->event_schedule_id;
                        $send_invitation->price_type_id = $sendInvitation->price_type_id;
                        $send_invitation->is_send_to_list = $sendInvitation->is_send_to_list;
                        $send_invitation->send_to_id = $guest['guest_id'];
                        $send_invitation->save();
                    }
                }
            } else if(!empty($args['send_to_id'])) {
                foreach($args['send_to_id'] as $send_to) {
                    $send_invitation = new Models\SendInvitation;
                    $send_invitation->event_id = $sendInvitation->event_id;
                    $send_invitation->event_schedule_id = $sendInvitation->event_schedule_id;
                    $send_invitation->price_type_id = $sendInvitation->price_type_id;
                    $send_invitation->is_send_to_list = $sendInvitation->is_send_to_list;
                    $send_invitation->send_to_id = $send_to['id'];
                    $send_invitation->save();
                }
            }
            $result['data'] = $send_invitation->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Invitation could not be sent. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Invitation could not be sent. Please, try again.', '', 1);
    }
})->add(new ACL('CanSendInvitation'));

/**
 * DELETE occupationOccupationIdDelete
 * Summary: Delete Occupation
 * Notes: Deletes a single Occupation based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/occupation/{occupationId}', function($request, $response, $args) {
	$occupation = Models\Occupation::find($request->getAttribute('occupationId'));
	if(!empty($occupation)) {
        try {
            $occupation->delete();
            $result = array(
                'status' => 'success',
            );
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Occupation could not be deleted. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Occupation could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteOccupation'));


/**
 * GET occupationOccupationIdGet
 * Summary: Fetch Occupation
 * Notes: Returns a Occupation based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/occupation/{occupationId}', function($request, $response, $args) {
	$occupation = Models\Occupation::find($request->getAttribute('occupationId'));
	if(!empty($occupation)) {
        $result = $occupation->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'Occupation could not be found. Please, try again.', '', 1);
    }
});


/**
 * PUT occupationOccupationIdPut
 * Summary: Update Occupation by its id
 * Notes: Update Occupation by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/occupation/{occupationId}', function($request, $response, $args) {
	$args = $request->getParsedBody();
	$occupation = Models\Occupation::find($request->getAttribute('occupationId'));
	if(!empty($occupation)) {
        foreach ($args as $key => $arg) {
            $occupation->{$key} = $arg;
        }
        $occupation->slug = Inflector::slug(strtolower($occupation->name) , '-');
        $result = array();
        try {
            $validationErrorFields = $occupation->validate($args);
            if (empty($validationErrorFields)) {
                $occupation->save();
                $result = $occupation->toArray();
                return renderWithJson($result);
            } else {
                return renderWithJson($result, 'Occupation could not be updated. Please, try again.', $validationErrorFields, 1);
            }
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Occupation could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Occupation could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateOccupation'));


/**
 * GET occupationsGet
 * Summary: Fetch all Occupations
 * Notes: Returns all Occupations from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/occupations', function($request, $response, $args) {
    $queryParams = $request->getQueryParams();
	$results = array();
	try {
        if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
            $occupations['data'] = Models\Occupation::Filter($queryParams)->get()->toArray();    
        } else {
            $count = PAGE_LIMIT;
            if(!empty($queryParams['limit'])) {
                $count = $queryParams['limit'];
            }
		    $occupations = Models\Occupation::Filter($queryParams)->paginate($count)->toArray();
        }
		$data = $occupations['data'];
		unset($occupations['data']);
		$results = array(
			'data' => $data,
			'_metadata' => $occupations
		);
		return renderWithJson($results);
	}
	catch(Exception $e) {
		return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
	}
});


/**
 * POST occupationsPost
 * Summary: Creates a new Occupation
 * Notes: Creates a new Occupation
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/occupations', function($request, $response, $args) {
	$args = $request->getParsedBody();
	$occupation = new Models\Occupation;
	foreach ($args as $key => $arg) {
		$occupation->{$key} = $arg;
	}
    $occupation->slug = Inflector::slug(strtolower($occupation->name) , '-');
	$result = array();
	try {
		$validationErrorFields = $occupation->validate($args);
		if (empty($validationErrorFields)) {
            $occupation->save();
			$result = $occupation->toArray();
			return renderWithJson($result);
		} else {
			return renderWithJson($result, 'Occupation could not be added. Please, try again.', $validationErrorFields, 1);
		}
	}
	catch(Exception $e) {
		return renderWithJson($result, 'Occupation could not be added. Please, try again.', '', 1);
	}
})->add(new ACL('canCreateOccupation'));


/**
 * DELETE educationsEducationIdDelete
 * Summary: Delete Education
 * Notes: Deletes a single Education based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/educations/{educationId}', function($request, $response, $args) {
	$education = Models\Education::find($request->getAttribute('educationId'));
	if(!empty($education)) {
        try {
            $education->delete();
            $result = array(
                'status' => 'success',
            );
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Education could not be deleted. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Education could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteEducation'));


/**
 * GET educationsEducationIdGet
 * Summary: Fetch Education
 * Notes: Returns a Education based on a single ID
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/educations/{educationId}', function($request, $response, $args) {
	$education = Models\Education::find($request->getAttribute('educationId'));
	if(!empty($education)) {
        $result = $education->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'Education could not be found. Please, try again.', '', 1);
    }
});


/**
 * PUT educationsEducationIdPut
 * Summary: Update Education by its id
 * Notes: Update Education by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/educations/{educationId}', function($request, $response, $args) {
	$args = $request->getParsedBody();
	$education = Models\Education::find($request->getAttribute('educationId'));
    if(!empty($education)) {        
        foreach ($args as $key => $arg) {
            $education->{$key} = $arg;
        }
        $education->slug = Inflector::slug(strtolower($education->name) , '-');
        $result = array();
        try {
            $validationErrorFields = $education->validate($args);
            if (empty($validationErrorFields)) {
                $education->save();
                $result = $education->toArray();
                return renderWithJson($result);
            } else {
                return renderWithJson($result, 'Education could not be updated. Please, try again.', $validationErrorFields, 1);
            }
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Education could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Education could not be updated. Please, try again.', '', 1);
    }
})->add(new ACL('canUpdateEducation'));


/**
 * GET educationsGet
 * Summary: Fetch all Educations
 * Notes: Returns all Educations from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/educations', function($request, $response, $args) {
	$queryParams = $request->getQueryParams();
	$results = array();
	try {
        if(!empty($queryParams['limit']) && $queryParams['limit'] == 'all') {
            $educations['data'] = Models\Education::Filter($queryParams)->get()->toArray();    
        } else {
            $count = PAGE_LIMIT;
            if(!empty($queryParams['limit'])) {
                $count = $queryParams['limit'];
            }
            $educations = Models\Education::Filter($queryParams)->paginate($count)->toArray();
        }
		$data = $educations['data'];
		unset($educations['data']);
		$results = array(
			'data' => $data,
			'_metadata' => $educations
		);
		return renderWithJson($results);
	}
	catch(Exception $e) {
		return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
	}
});


/**
 * POST educationsPost
 * Summary: Creates a new Education
 * Notes: Creates a new Education
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/educations', function($request, $response, $args) {
	$args = $request->getParsedBody();
	$education = new Models\Education;
	foreach ($args as $key => $arg) {
		$education->{$key} = $arg;
	}
    $education->slug = Inflector::slug(strtolower($education->name) , '-');
	$result = array();
	try {
		$validationErrorFields = $education->validate($args);
		if (empty($validationErrorFields)) {
			$education->save();
			$result = $education->toArray();
			return renderWithJson($result);
		} else {
			return renderWithJson($result, 'Education could not be added. Please, try again.', $validationErrorFields, 1);
		}
	}
	catch(Exception $e) {
		return renderWithJson($result, 'Education could not be added. Please, try again.', '', 1);
	}
})->add(new ACL('canCreateEducation'));

/**
 * POST Gift Voucher Post
 * Summary: Create New Gift Voucher
 * Notes: Create Gift Voucher.
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/gift_vouchers', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $giftVoucher = new Models\GiftVoucher;
    if (!empty($_GET['token'])) {
        $oauthAccessToken = Models\OauthAccessToken::where('access_token', $_GET['token'])->first()->toArray();
        if (count($oauthAccessToken) > 0) {
            if (!empty($oauthAccessToken['user_id'])) {
                $authUser = Models\User::where('username', $oauthAccessToken['user_id'])->first();
            }
        }
    }
    $cart = new Models\Cart;
    foreach ($args as $key => $arg) {
        if ($key != 'session_id') {
            $giftVoucher->user_id = $authUser['id'];
            $giftVoucher->{$key} = $arg;
            $giftVoucher->code = mt_rand(100000, 999999);
        }
    }
    $giftVoucher->avaliable_amount = $args['amount'];
    $result = array();
    try {
        $validationErrorFields = $giftVoucher->validate($args);
        if (empty($validationErrorFields)) {
            $giftVoucher->save();
            $cart->gift_voucher_id = $giftVoucher->id;
            $cart->user_id = $authUser['id'];
            $cart->event_id = 0;
            $cart->venue_zone_section_seat_id = 0;
            $cart->price = $args['amount'];
            $cart->session_id = $args['session_id'];
            $cart->save();
            $result['data'] = $cart->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'Gift voucher could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Gift voucher could not be added. Please, try again.', '', 1);
    }
})->add(new ACL('canCreateGiftVoucher'));
/**
 * GET GiftVoucher
 * Summary: Fetch all GiftVoucher
 * Notes: Returns all GiftVoucher from the system
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/gift_vouchers', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $giftVoucher = Models\GiftVoucher::Filter($queryParams)->paginate(20)->toArray();
        $data = $giftVoucher['data'];
        unset($giftVoucher['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $giftVoucher
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
})->add(new ACL('canListGiftVoucher'));
$app->GET('/api/v1/price_types', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $priceType = Models\PriceType::Filter($queryParams)->paginate(20)->toArray();
        $data = $priceType['data'];
        unset($priceType['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $priceType
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
/**
 * POST Cart Post
 * Summary: Create New Cart
 * Notes: Create Cart
 * Output-Formats: [application/json]
 */
$app->POST('/api/v1/carts', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $cart = new Models\Cart;
    if (!empty($_GET['token'])) {
        $oauthAccessToken = Models\OauthAccessToken::where('access_token', $_GET['token'])->first()->toArray();
        if (count($oauthAccessToken) > 0) {
            if (!empty($oauthAccessToken['user_id'])) {
                $authUser = Models\User::where('username', $oauthAccessToken['user_id'])->first();
            }
        }
    }
    foreach ($args as $key => $arg) {
        $cart = new Models\Cart;
        $cart->session_id = $args['session_id'];
        $cart->user_id = $authUser['id'];
        foreach ($arg as $field => $value) {
            $cart->{$field} = $value;
        }
        $cart->save();
        $cart_id[] = $cart->id;
    }
    $result = array();
    try {
        $cart = Models\Cart::where('session_id', $cart->session_id)->findMany($cart_id);
        $result['data'] = $cart->toArray();
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Cart voucher could not be added. Please, try again.', '', 1);
    }
});
/**
 * DELETE NewsCategory
 * Summary: Delete NewsCategory
 * Notes: Deletes a single VenueZoneNewsCategory  based on the ID supplied
 * Output-Formats: [application/json]
 */
$app->DELETE('/api/v1/carts/{sessionId}', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    if (!empty($queryParams['cartId'])) {
        $cart = Models\Cart::where('id', $queryParams['cartId'])->where('session_id', $request->getAttribute('sessionId'))->first();
    } else {
        $cart = Models\Cart::where('session_id', $request->getAttribute('sessionId'))->first();
    }
    try {
        if (!empty($cart)) {
            $cart->delete();
            $result = array(
                'status' => 'success',
            );
            return renderWithJson($result);
        } else {
            $result = array();
            return renderWithJson($result, 'cart could not be deleted. Please, try again.', '', 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'cart could not be deleted. Please, try again.', '', 1);
    }
});
/**
 * PUT Cart
 * Summary: Update Cart by its id
 * Notes: Update Cart by its id
 * Output-Formats: [application/json]
 */
$app->PUT('/api/v1/carts/{sessionId}', function ($request, $response, $args)
{
    $result = array();
    $args = $request->getParsedBody();
    if (!empty($request->getAttribute('sessionId'))) {
        $cart = Models\Cart::where('session_id', $request->getAttribute('sessionId'))->update(['user_id' => $args['user_id']]);
    } else {
        return renderWithJson($result, 'Session value empty.', '', 1);
    }
    try {
        if (!isset($args['is_donation'])) {
            $args['is_donation'] = 0;
        }
        $cart = Models\Cart::where('session_id', $request->getAttribute('sessionId'))->get();
        $result = $cart->toArray();
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'cart could not be updated. Please, try again.', '', 1);
    }
});
$app->GET('/api/v1/carts/{sessionId}', function ($request, $response, $args)
{
    $result = array();
    $cart = Models\Cart::with('events', 'venue_zone_section_seats', 'price_type', 'event_schedule')->where('session_id', $request->getAttribute('sessionId'))->get();
    if (!empty($cart)) {
        $result['data'] = $cart->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
$app->GET('/api/v1/credit_cards', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        if (!empty($_GET['token'])) {
            $oauthAccessToken = Models\OauthAccessToken::where('access_token', $_GET['token'])->first()->toArray();
            if (count($oauthAccessToken) > 0) {
                if (!empty($oauthAccessToken['user_id'])) {
                    $authUser = Models\User::where('username', $oauthAccessToken['user_id'])->first();
                }
            }
        }
        $creditCard = Models\CreditCard::where('user_id', $authUser['id'])->Filter($queryParams)->paginate(20)->toArray();
        $data = $creditCard['data'];
        unset($creditCard['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $creditCard
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
$app->GET('/api/v1/donation_amounts', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $donationAmount = Models\DonationAmount::Filter($queryParams)->paginate(20)->toArray();
        $data = $donationAmount['data'];
        unset($donationAmount['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $donationAmount
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
$app->POST('/api/v1/checkout', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $checkout = new Models\Cart;
    foreach ($args as $key => $arg) {
        $checkout->{$key} = $arg;
    }
    $result = array();
    try {
        $validationErrorFields = $checkout->validate($args);
        if (empty($validationErrorFields)) {
            if (!empty($args['session_id'])) {
                if (!empty($args['delivery_method_id'])) {
                    $checkout = Models\Cart::where('session_id', $args['session_id'])->update(['is_reserved' => 1, 'delivery_method_id' => $args['delivery_method_id']]);
                }
                $checkout = Models\Cart::where('session_id', $args['session_id'])->update(['is_reserved' => 1]);
                $checkoutGet = Models\Cart::where('session_id', $args['session_id'])->get();
                $result['data'] = $checkoutGet->toArray();
                return renderWithJson($result);
            } else {
                return renderWithJson($result, 'Could not find session value', 1);
            }
        } else {
            return renderWithJson($result, 'checkout could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'checkout could not be added. Please, try again.', '', 1);
    }
});
$app->GET('/api/v1/venue_services', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $venueService = Models\VenueService::Filter($queryParams)->paginate(20)->toArray();
        $data = $venueService['data'];
        unset($venueService['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $venueService
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
$app->DELETE('/api/v1/venue_services/{VenueServiceId}', function ($request, $response, $args)
{
    $result = array();
    $venueService = Models\VenueService::find($request->getAttribute('VenueServiceId'));
    try {
        $venueService->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Venue Service could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteLanguage'));
$app->PUT('/api/v1/venue_services/{VenueServiceId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $venueService = Models\VenueService::find($request->getAttribute('VenueServiceId'));
    $validationErrorFields = $venueService->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $venueService->{$key} = $arg;
        }
        try {
            $venueService->save();
            $result['data'] = $venueService->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'venue Service could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'venue Service could not be added. Please, try again.', $validationErrorFields, 1);
    }
});
$app->GET('/api/v1/venue_services/{VenueServiceId}', function ($request, $response, $args)
{
    $result = array();
    $venueService = Models\VenueService::find($request->getAttribute('VenueServiceId'));
    if (!empty($venueService)) {
        $result['data'] = $venueService->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'Venue Service not found', '', 1);
    }
});
$app->POST('/api/v1/venue_services', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $venueService = new Models\VenueService;
    foreach ($args as $key => $arg) {
        $venueService->{$key} = $arg;
    }
    $result = array();
    try {
        $validationErrorFields = $venueService->validate($args);
        if (empty($validationErrorFields)) {
            $venueService->save();
            $result['data'] = $venueService->toArray();
            return renderWithJson($result);
        } else {
            return renderWithJson($result, 'VenueService could not be added. Please, try again.', $validationErrorFields, 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($result, 'VenueService could not be added. Please, try again.', '', 1);
    }
});
$app->GET('/api/v1/delivery_methods', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $results = array();
    try {
        $deliveryMethod = Models\DeliveryMethod::Filter($queryParams)->paginate(20)->toArray();
        $data = $deliveryMethod['data'];
        unset($deliveryMethod['data']);
        $results = array(
            'data' => $data,
            '_metadata' => $deliveryMethod
        );
        return renderWithJson($results);
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
$app->GET('/api/v1/gift_vouchers/{couponCode}', function ($request, $response, $args)
{
    $results = array();
    try {
        $giftVoucher = Models\GiftVoucher::orwhere('code', $request->getAttribute('couponCode'))->orwhere('id', $request->getAttribute('couponCode'))->get()->toArray();
        if (!empty($giftVoucher)) {
            $result['data'] = $giftVoucher;
            return renderWithJson($result);
        } else {
            return renderWithJson($results, 'No record found', '', 1);
        }
    }
    catch(Exception $e) {
        return renderWithJson($results, $message = 'No record found', $fields = '', $isError = 1);
    }
});
$app->DELETE('/api/v1/credit_cards/{creditCardId}', function ($request, $response, $args)
{
    $result = array();
    $creditCard = Models\CreditCard::find($request->getAttribute('creditCardId'));
    try {
        $creditCard->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'Credit card could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('canDeleteCreditCard'));
/**
 * GET paymentGatewaysSudopaySynchronizeGet
 * Summary: Get sudopay synchronize details
 * Notes: Get sudopay synchronize details
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/payment_gateways/sudopay_synchronize', function ($request, $response, $args)
{
    $result = array();
    $paymentGateway = new Models\PaymentGateway();
    $sudoPaymentSettings = Models\PaymentGatewaySetting::where('payment_gateway_id', 1)->get();
    foreach ($sudoPaymentSettings as $value) {
        $sudpay_synchronize[$value->name] = $value->test_mode_value;
    }
    $s = new SudoPay_API(array(
        'api_key' => $sudpay_synchronize['sudopay_api_key'],
        'merchant_id' => $sudpay_synchronize['sudopay_merchant_id'],
        'website_id' => $sudpay_synchronize['sudopay_website_id'],
        'secret_string' => $sudpay_synchronize['sudopay_secret_string'],
        'is_test' => true,
        'cache_path' => ''
    ));
    $currentPlan = $s->callPlan();
    $plantype = $s->plantype();
    if (!empty($currentPlan['error']['message'])) {
        return renderWithJson($result, $currentPlan['error']['message'], '', 1);
    } else {
        if ($currentPlan['brand'] == 'Transparent Branding') {
            $plan = $plantype['TransparentBranding'];
        } elseif ($currentPlan['brand'] == 'SudoPay Branding') {
            $plan = $plantype['VisibleBranding'];
        } elseif ($currentPlan['brand'] == 'Any Branding') {
            $plan = $plantype['AnyBranding'];
        }
        $paymentGatewaySetting = new Models\PaymentGatewaySetting();
        if ($plantype['is_test_mode']) {
            $payment_gateway_api = $paymentGatewaySetting->where('name', 'is_payment_via_api')->where('payment_gateway_id', 1)->first();
            $payment_gateway_api->test_mode_value = $plan;
            $payment_gateway_api->save();
            $payment_gateway_plan = $paymentGatewaySetting->where('name', 'sudopay_subscription_plan')->where('payment_gateway_id', 1)->first();
            $payment_gateway_plan->test_mode_value = $currentPlan['name'];
            $payment_gateway_plan->save();
        } else {
            $payment_gateway_api = $paymentGatewaySetting->where('name', 'is_payment_via_api')->where('payment_gateway_id', 1)->first();
            $payment_gateway_api->live_mode_value = $plan;
            $payment_gateway_api->save();
            $payment_gateway_plan = $paymentGatewaySetting->where('name', 'sudopay_subscription_plan')->where('payment_gateway_id', 1)->first();
            $payment_gateway_plan->live_mode_value = $currentPlan['name'];
            $payment_gateway_plan->save();
        }
        $gateway_response = $s->callGateways();
        foreach ($gateway_response['gateways'] as $gateway_group) {
            $sudo_groups = new Models\SudopayPaymentGroup;
            $sudo_groups->sudopay_group_id = $gateway_group['id'];
            $sudo_groups->name = $gateway_group['name'];
            $sudo_groups->thumb_url = $gateway_group['thumb_url'];
            $sudo_groups->save();
            foreach ($gateway_group['gateways'] as $gateway) {
                $sudo_payment_gateways = new Models\SudopayPaymentGateway;
                $supported_actions = $gateway['supported_features'][0]['actions'];
                $sudo_payment_gateways->is_marketplace_supported = 0;
                if (in_array('Marketplace-Auth', $supported_actions)) {
                    $sudo_payment_gateways->is_marketplace_supported = 1;
                }
                $sudo_payment_gateways->sudopay_gateway_id = $gateway['id'];
                $sudo_payment_gateways->sudopay_gateway_details = serialize($gateway);
                $sudo_payment_gateways->sudopay_gateway_name = $gateway['display_name'];
                $sudo_payment_gateways->sudopay_payment_group_id = $sudo_groups->id;
                $sudo_payment_gateways->save();
            }
        }
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
})->add(new ACL('canUpdatePaymentGateway'));
/**
 * GET paymentGatewayGet
 * Summary: Get  payment gateways
 * Notes: Filter payment gateway.
 * Output-Formats: [application/json]
 */
$app->GET('/api/v1/payment_gateways/{paymentGatewayId}', function ($request, $response, $args)
{
    $result = array();
    $paymentGateway = Models\PaymentGateway::with('payment_settings')->find($request->getAttribute('paymentGatewayId'));
    if (!empty($paymentGateway)) {
        $result['data'] = $paymentGateway->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'No record found', '', 1);
    }
})->add(new ACL('canViewPaymentGateway'));
$app->DELETE('/api/v1/gift_vouchers/{giftVoucherId}', function ($request, $response, $args)
{
    $giftVoucherId = Models\GiftVoucher::find($request->getAttribute('giftVoucherId'));
    try {
        $giftVoucherId->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'gift Voucher  could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('CanDeleteGiftVoucher'));
$app->GET('/api/v1/currencies', function ($request, $response, $args)
{
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
        $currencies = Models\Currency::Filter($queryParams)->paginate(PAGE_LIMIT)->toArray();
        $data = $currencies['data'];
        unset($currencies['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $currencies
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
$app->GET('/api/v1/event_schedules', function ($request, $response, $args)
{
    if (!empty($_GET['token'])) {
        $authUser = getUserDetails($_GET['token']);
    }
    $queryParams = $request->getQueryParams();
    $result = array();
    try {
       $eventSchedule = Models\EventSchedule::with('event_schedule_zone');
         if(!empty($authUser['role_id'] == \Constants\ConstUserTypes::EventOrganizer))
        {
          $events = Models\Event::where('user_id',$authUser['id'])->select('id')->get()->toArray();
            if(!empty($events))
            {   
            foreach($events as $values)
            {
               $eventsValue =  $values['id'];
               $eventSchedule->where('event_id',$eventsValue);
            }
            }         
        }
        $eventSchedules = $eventSchedule->Filter($queryParams)->paginate(20)->toArray();
        $data = $eventSchedules['data'];
        unset($eventSchedules['data']);
        $result = array(
            'data' => $data,
            '_metadata' => $eventSchedules
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'No record found', '', 1);
    }
});
$app->DELETE('/api/v1/event_schedules/{EventScheduleId}', function ($request, $response, $args)
{
    $result = array();
    $eventSchedule = Models\EventSchedule::find($request->getAttribute('EventScheduleId'));
    try {
        $eventSchedule->delete();
        $result = array(
            'status' => 'success',
        );
        return renderWithJson($result);
    }
    catch(Exception $e) {
        return renderWithJson($result, 'EventS chedule could not be deleted. Please, try again.', '', 1);
    }
})->add(new ACL('CanDeleteEventSchedule'));
$app->PUT('/api/v1/event_schedules/{EventScheduleId}', function ($request, $response, $args)
{
    $args = $request->getParsedBody();
    $result = array();
    $eventSchedule = Models\EventSchedule::find($request->getAttribute('EventScheduleId'));
    $validationErrorFields = $eventSchedule->validate($args);
    if (empty($validationErrorFields)) {
        foreach ($args as $key => $arg) {
            $eventSchedule->{$key} = $arg;
        }
        try {
            $eventSchedule->save();
            $result['data'] = $eventSchedule->toArray();
            return renderWithJson($result);
        }
        catch(Exception $e) {
            return renderWithJson($result, 'Event Schedule could not be updated. Please, try again.', '', 1);
        }
    } else {
        return renderWithJson($result, 'Event Schedule  could not be added. Please, try again.', $validationErrorFields, 1);
    }
})->add(new ACL('CanUpdateEventSchedule'));
$app->GET('/api/v1/event_schedules/{EventScheduleId}', function ($request, $response, $args)
{
    $result = array();
    $eventSchedule = Models\EventSchedule::with('event_schedule_zone')->find($request->getAttribute('EventScheduleId'));
    if (!empty($eventSchedule)) {
        $result['data'] = $eventSchedule->toArray();
        return renderWithJson($result);
    } else {
        return renderWithJson($result, 'Event Schedule  not found', '', 1);
    }
});
$app->POST('/api/v1/attachments', function ($request, $response, $args)
{
    $args = $request->getQueryParams();
    $file = $request->getUploadedFiles();
    $newfile = $file['file'];
    $type = pathinfo($newfile->getClientFilename() , PATHINFO_EXTENSION);
    $name = md5(time());
    list($width, $height) = getimagesize($file['file']->file);
    if ($args['class'] == 'Event' && $width < 1024 && $height < 768) {
        $validationErrorFields['image'] = 'InvalidDimension';
    }
    if (!file_exists(APP_PATH . '/media/tmp/')) {
        mkdir(APP_PATH . '/media/tmp/', 0777, true);
    }
    $response = array();
    if (empty($validationErrorFields) && move_uploaded_file($newfile->file, APP_PATH . '/media/tmp/' . $name . '.' . $type) === true) {
        $attachment->filename = $name . '.' . $type;
        $response = array(
            'attachment' => $attachment->filename,
            'error' => array(
                'code' => 0,
                'message' => ''
            )
        );
        return renderWithJson($response);
    } else {
        return renderWithJson($response, 'Image not uploaded', $validationErrorFields, 1);
    }
});
$app->run();