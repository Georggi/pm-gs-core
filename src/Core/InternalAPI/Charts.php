<?php
namespace Core\InternalAPI;

use Core\BaseFiles\BaseAPI;

class Charts extends BaseAPI{
    /** @var array */
    private $smiles = [
        /* IMPORTANT NOTE:
         * This is a 'Fixed' array, so the 'numeric position' will be used as the 'ASCII Character Code'
         * So, the codes that are not 'in use' or 'doesn't work' will not be set :P
         */
           1 => [   # ☺
            ":)", "(:", "/smile/"
        ], 2 => [   # ☻
            ":D", "/smile2/"
        ], 3 => [   # ♥
            "<3", "/heart/"
        ], 4 => [   # ♦
            "<>", "/diamond/"
        ], 5 => [   # ♣
            "/clubs/"
        ], 6 => [   # ♠
            "/spades/",
        ], 7 => [   # •
            "/moon/", "/fullmoon/"
        ], 8 => [   # ◘
            "/moon2/", "/fullmoon2/"
        ], 9 => [   # ○
            "/moon3/", "/newmoon/"
        ], 11 => [  # ♂
            "/male/"
        ], 12 => [  # ♀
            "/female/"
        ], 13 => [  # ♪
            "/music/"
        ], 14 => [  # ♫
            "/music2/"
        ], 15 => [  #☼
            "/sun/"
        ], 16 => [  # ►
            "/right/"
        ], 17 => [  # ◄
            "/left/"
        ], 18 => [  # ↕
            "/updown/"
        ], 23 => [  # ↨
            "/updown2/"
        ], 24 => [  #↑
            "/uparrow/"
        ], 25 => [  # ↓
            "/downarrow/"
        ], 26 => [  # →
            "/rightarrow"
        ], 27 => [  # ←
            "/leftarrow/"
        ], 29 => [  # ↔
            "/leftright/"
        ], 30 => [  # ▲
            "/up/"
        ], 31 => [  # ▼
            "/down/"
        ]
    ];
    /** @var array */
    private $allowedAds = [
        "minepocket.com",
        "hostingitall.com",
        "iksaku@me.com"
    ];
    /** @var array*/
    private $bannedWords = [];

    /**
     * @return array
     */
    public function getAll(){
        return [
            "smiles" => $this->smiles,
            "allowedAds" => $this->allowedAds,
            "badWords" => $this->bannedWords
        ];
    }

    /**
     * @param string $name
     * @return array|bool
     */
    public function get($name){
        if(!isset($this->{$name})){
            return false;
        }
        return $this->{$name};
    }

    /**
     * @param string $string
     * @return string
     */
    public function coloredChat($string){
        return preg_replace_callback(
            "/(\\\&|\&)[^0-9a-fk-or]/", // This checks for any match with the "&" character, with and without the prefixed "\" (Allowing to escape the color code)
            function(array $matches){ // Lambda expression to return the correct values of the "Replacement"
                return str_replace("\\§", "&", str_replace("&", "§", $matches[0])); // Replace "&" to "§", and escape "\§" to "&"
            },
            $string // The original message xD
        );
    }

    /**
     * @param string $string
     * @return string
     */
    public function convertSmiles($string){
        foreach($this->get("smiles") as $k => $v){ // Remember the fixed array, for more information, please check the "Charts" class and comments
            //$string = str_replace($v, chr((int) $k), $string);
            $string = str_replace($v, "", $string);
        }
        return $string;
    }

    /**
     * @param string $string
     * @return bool
     */
    public function wordFilter($string){
        // Temporary hide the allowed "Ads" :P
        if(is_array($this->get("allowedAds"))){
            $string = str_ireplace($this->get("allowedAds"), "", $string);
        }
        // Advertisement checker
        if(filter_var($string, FILTER_VALIDATE_URL) || (filter_var($string, FILTER_SANITIZE_URL) && ((is_int($u1 = preg_match("/\w+\.\w+\.\w+/", $string)) && $u1 > 0) || (is_int($u2 = preg_match("/\w+\.\w+/", $string)) && $u2 > 0)))){
            return 0;
        }
        // Bad words filter
        $badWordCount = null;
        if(is_array($this->get("badWords"))){
            str_ireplace($this->get("badWords"), "", $string, $badWordCount);
        }
        if(is_int($badWordCount)){
            return 1;
        }
        return true;
    }
}