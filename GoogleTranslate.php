<?php

class GoogleTranslater{
    private $errors = '';
    public function _construct(){
        if (!function_exists('curl_init')) {
            $this->errors = 'No CURL support';
        }
    }
    public function translateText($text, $fromLanguage = 'en', $toLanguage = 'ru', $translit = false){
        if (empty($this->errors)) {
            $result = '';
            for($i = 0; $i < strlen($text); $i += 1000)
            {
                $subText = substr($text, $i, 1000);
                $response = $this->_curlToGoogle('http://translate.google.com/translate_a/t?client=te&text='.urlencode($subText).'&hl=$toLanguage&sl=' . $fromLanguage . '&tl=' . $toLanguage . '&multires=1&otf=1&ssel=0&tsel=0&uptl=ru&sc=1');
                $result .= $this->_parceGoogleResponse($response, $translit);
            }
            return $result;
        } else {
            return false;
        }
    }
    public function translateArray(array $array, $fromLanguage = 'en', $toLanguage = 'ru', $translit = false){
        if (empty($this->errors)) {
            $text = implode('[<#>]', $array);
            $response = $this->translateText($text, $fromLanguage, $toLanguage, $translit);
            return $this->_explode($response);
        } else {
            return false;
        }
    }
    public function getLanguages(){
        if (empty($this->errors)) {
            $page = $this->_curlToGoogle('http://translate.google.com/');
            preg_match('%<select[^<]*?tl[^<]*?>(.*?)</select>%is', $page, $match);
            preg_match_all('%<option.*?value=\'(.*?)\'>(.*?)</option>%is', $match[0], $languages);
            $result = Array();
            for($i = 0; $i < count($languages[0]); $i++){
                $result[$languages[1][$i]] = $languages[2][$i];
            }
            return $result;
        } else {
            return false;
        }
    }

    public function getLanguagesHTML(){
        if (empty($this->errors)) {
            $page = $this->_curlToGoogle('http://translate.google.com/');
            preg_match('%<select[^<]*?tl[^<]*?>(.*?)</select>%is', $page, $match);
            return $match[1];
        } else {
            return false;
        }
    }

    public function getErrors(){
        return $this->errors;
    }

    private function _explode($text){
        $text = preg_replace('%\[\s*<\s*#\s*>\s*\]%', '[<#>]', $text);
        return array_map('trim', explode('[<#>]', $text));
    }

    private function _curlToGoogle($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (isset($_SERVER['HTTP_REFERER'])) {
            curl_setopt($curl, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
        }
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.71 Safari/534.24');
        $response = curl_exec($curl);
        if(curl_errno($curl))
        {
            $this->errors .=  'Curl Error: '.curl_error($curl);
            return false;
        }
        curl_close($curl);
        return $response;
    }

    private function _parceGoogleResponse($response, $translit = false){
        if (empty($this->errors)) {
            $result = '';
            $json = json_decode($response);
            foreach ($json->sentences as $sentence) {
                $result .= $translit ? $sentence->translit : $sentence->trans;
            }
            return $result;
        } else {
            return false;
        }
    }
}