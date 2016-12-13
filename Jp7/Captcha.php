<?php

/*
 * File: CaptchaSecurityImages.php
* Author: Simon Jarvis
* Copyright: 2006 Simon Jarvis
* Date: 03/08/06
* Updated: 23/11/06
* Requirements: PHP 4/5 with GD and FreeType libraries
* Link: http://www.white-hat-web-design.co.uk/articles/php-captcha.php
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details:
* http://www.gnu.org/licenses/gpl.html
*
*/

class Jp7_Captcha
{
    protected $font;
    protected $code;

    public function __construct()
    {
        $this->font = __DIR__.'/../fonts/DejaVuSans.ttf';
    }

    protected function generateCode($characters)
    {
        /* list all possible characters, similar looking characters and vowels have been removed */
        $possible = '23456789bcdfghjkmnpqrstvwxyz';
        $code = '';
        $i = 0;
        while ($i < $characters) {
            $code .= mb_substr($possible, mt_rand(0, mb_strlen($possible) - 1), 1);
            $i++;
        }

        return $code;
    }

    public function output($width = '120', $height = '80', $characters = '6')
    {
        $code = $this->generateCode($characters);
        /* font size will be 75% of the image height */
        $font_size = $height * 0.50;
        $image = @imagecreate($width, $height) or die('Cannot initialize new GD image stream');
        /* set the colours */
        $background_color = imagecolorallocate($image, 255, 255, 255);
        $text_color = imagecolorallocate($image, 255, 255, 255);
        $text_shadow_color = imagecolorallocate($image, 100, 180, 120);
        $noise_color = imagecolorallocate($image, 100, 180, 120);
        /* generate random dots in background */
        for ($i = 0; $i < ($width * $height) / 6; $i++) {
            imagefilledellipse($image, mt_rand(0, $width), mt_rand(0, $height), 1, 2, $noise_color);
        }
        for ($i = 0; $i < ($width * $height) / 6; $i++) {
            imagefilledellipse($image, mt_rand(0, $width), mt_rand(0, $height), 1, 2, $noise_color);
        }
        /* generate random lines in background */
        for ($i = 0; $i < ($width * $height) / 150; $i++) {
            imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $noise_color);
        }
        /* create textbox and add text */
        $textbox = imagettfbbox($font_size, 0, $this->font, $code) or die('Error in imagettfbbox function');
        $x = ($width - $textbox[4]) / 2;
        $y = ($height - $textbox[5]) / 2;
        imagettftext($image, $font_size, 0, $x + 2, $y + 2, $text_shadow_color, $this->font, $code) or die('Error in imagettftext function');
        imagettftext($image, $font_size, 0, $x, $y, $text_color, $this->font, $code) or die('Error in imagettftext function');
        /* output captcha image to browser */
        imagejpeg($image);
        imagedestroy($image);

        $this->code = $code;
    }
    public function getFont()
    {
        return $this->font;
    }

    public function setFont($font)
    {
        $this->font = $font;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }
}
