<?php

/**
 * Handle Start Start API Charges
 *
 * @author Yazin Alirhayim <yazin@payfort.com>
 * @link https://start.payfort.com/docs/
 * @license http://opensource.org/licenses/MIT
 */
class Start_Charge {

    /**
     * Create a new charge for given $data
     *
     * @param array $data the data for the transaction
     * @return array the result of the transaction
     * @throws Start_Error_Authentication if the API Key is invalid
     * @throws Start_Error_Banking if the card could not be accepted
     * @throws Start_Error_Processing if the there's a failure from Start
     * @throws Start_Error_Request if any of the parameters is invalid
     * @throws Start_Error if there is a general error in the API endpoint
     * @throws Exception for any other errors
     */
    public static function create(array $data) {
        $return_data = Start_Request::make_request("charge", $data);
        return $return_data;
    }

    /**
     * List all created charges
     *
     * @return array list of transactions
     * @throws Start_Error_Parameters if any of the parameters is invalid
     * @throws Start_Error_Authentication if the API Key is invalid
     * @throws Start_Error if there is a general error in the API endpoint
     * @throws Exception for any other errors
     */
    public static function all() {
        $return_data = Start_Request::make_request("charge_list");
        return $return_data;
    }
}
