# Tixmall

Tixmall is an open source Event Booking Solutions that is capable to quickly set up, activate and optimize an event
management marketplace. It is written in AngularJS with REST API for high performance in mind.

> This is project is part of Agriya Open Source efforts. Tixmall was originally a paid script and was selling around 10000 Euros. It is now released under dual license (OSL 3.0 & Commercial) for open source community benefits.

![tixbox_banner](https://user-images.githubusercontent.com/4700341/48555231-96919900-e906-11e8-91c1-47188e1e5686.png)
![tixbox_event](https://user-images.githubusercontent.com/4700341/48555232-96919900-e906-11e8-850b-096fd894d29c.png)

## Support

Tixmall is an open source project. Full commercial support (commercial license, customization, training, etc) are available through [Tixmall platform support](https://www.agriya.com/solutions/event-booking-solution)

Theming partner [CSSilize for design and HTML conversions](http://cssilize.com/)

## Getting Started

### Prerequisites

#### For deployment

* PostgreSQL
* PHP >= 5.5.9 with OpenSSL, PDO, Mbstring and cURL extensions
* Nginx (preferred) or Apache

#### For building (build tools)

* Nodejs
* Composer
* Bower
* Grunt

### Setup

* PHP dependencies are handled through `composer` (Refer `/server/php/Slim/`)
* JavaScript dependencies are handled through `bower` (Refer `/client/`)
* Needs writable permission for `/tmp/` and `/media/` folders found within project path
* Build tasks are handled through `grunt`
* Database schema `/sql/tixmall.sql`

### Contributing

Our approach is similar to Magento. If anything is not clear, please [contact us](https://www.agriya.com/contact).

All Submissions you make to Tixmall through GitHub are subject to the following terms and conditions:

* You grant Agriya a perpetual, worldwide, non-exclusive, no charge, royalty free, irrevocable license under your applicable copyrights and patents to reproduce, prepare derivative works of, display, publicly perform, sublicense and distribute any feedback, ideas, code, or other information ("Submission") you submit through GitHub.
* Your Submission is an original work of authorship and you are the owner or are legally entitled to grant the license stated above.


### License

Copyright (c) 2014-2018 [Agriya](https://www.agriya.com/).

Dual License (OSL 3.0 & [Commercial License](https://www.agriya.com/contact))
