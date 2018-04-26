<?php

/*
 * Copyright (c) 2018 Karim Kalunga
 *
 * Licensed under The MIT License,
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://opensource.org/licenses/MIT
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 */

/**
 * <p>
 *     Here is where all your end-points for data exchanges are defined
 * </p>
 *
 * @author Karim Kalunga(mobodev)
 *         karimkalunga@endlesstec.com
 *
 */
define('PAYMENT_JSON_OBJECT','payment');
define('PAYMENT_STATUS_CODE','status');
define('PAYMENT_REFERENCE_ID','refId');
define('PAYMENT_REFERENCE_UID','refUID');
define('PAYMENT_LOG_TIMESTAMP','logged_timestamp');
define('PAYMENT_CALLBACK_STATUS','callback');
define('PAYMENT_CALLBACK_TIMESTAMP','callback_timestamp');
define('PAYMENT_DEFAULT_VALUE','waiting');
define('RESPONSE_ERROR_STATUS','error');
define('RESPONSE_MESSAGE','message');

define('TIGOPESA_SANDBOX_ENVIRONMENT','https://securesandbox.tigo.com/v1/');
define('TIGOPESA_LIVE_ENVIRONMENT','https://secure.tigo.com/v1/');

define('STRIPE_RESPONSE_STATUS','status');
define('STRIPE_RESPONSE_REASON','reason');
define('STRIPE_RESPONSE_RISK_LEVEL','risk');
define('STRIPE_RESPONSE_PAYMENT_TYPE','type');
define('STRIPE_RESPONSE_ISPAID','paid');
define('STRIPE_RESPONSE_PAYMENT_ID','id');
define('STRIPE_RESPONSE_CARD','card');
define('STRIPE_RESPONSE_AMOUNT_DEDUCTED','amount');
define('STRIPE_RESPONSE_PAYMENT_TIMESTAMP','created');
define('STRIPE_RESPONSE_COUNTRY','country');