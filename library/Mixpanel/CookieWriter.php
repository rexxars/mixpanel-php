<?php
/**
 * PHP Mixpanel tracker
 *
 * Copyright (c) 2013 Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */

namespace Mixpanel;

/**
 * Cookie writer, to ease unit testing
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
class CookieWriter {

    /**
     * Returns whether we can send cookies or not
     *
     * @return boolean
     */
    public function canSend() {
        return !headers_sent();
    }

    /**
     * Sets a cookie
     *
     * @param string  $name     The name of the cookie
     * @param string  $value    The value of the cookie
     * @param integer $expire   The time the cookie expires (timestamp)
     * @param string  $path     The path on the server in which the cookie will be available on
     * @param string  $domain   The domain that the cookie is available to
     * @param boolean $secure   Indicates that the cookie should only be sent over HTTPS from the client
     * @param boolean $httponly When TRUE the cookie will be made accessible only through the HTTP protocol
     * @return boolean
     */
    public function setCookie($name, $value, $expire = 0, $path = null,
        $domain = null, $secure = false, $httponly = false) {

        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

}