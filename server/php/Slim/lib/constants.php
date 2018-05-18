<?php
namespace Constants;
class ConstUserTypes
{
    const Admin = 1;
    const User = 2;
    const EventOrganizer = 3;
}
class UserCashWithdrawStatus
{
    const Pending = 0;
    const Approved = 1;
    const Rejected = 2;
}
class Transactions
{
    const Order = 'Order';
    const Wallet = 'Wallet';
}
class SocialLogins
{
    const Facebook = 1;
    const Twitter = 2;
    const GooglePlus = 3;
}
class PaymentGateways
{
    const Payfort = 1;
}
class OrderStatus
{
    const Draft = 0;
    const Processing = 1;
    const Captured = 2;
    const PaymentFailed = 3;
}
class EventStatus
{
    const Open = 1;
    const Sold = 2;
    const Closed = 3;
}
