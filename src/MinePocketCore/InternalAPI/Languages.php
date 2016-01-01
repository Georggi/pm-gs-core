<?php
namespace Core\InternalAPI;

use Core\Loader;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class Languages{
    /** @var Loader */
    private $plugin;

    public function __construct(Loader $core){
        $this->plugin = $core;
    }

    /** @var array */
    private $english = [
        "motd" => TextFormat::YELLOW . "/right/" . TextFormat::GRAY . " ---------------------------- " . TextFormat::YELLOW . "/left/\n" .
            TextFormat::YELLOW . "Welcome to " . TextFormat::AQUA . "MinePocket" . TextFormat::LIGHT_PURPLE . " Network" . TextFormat::YELLOW . "!\n" .
            TextFormat::YELLOW . "/right/" . TextFormat::GRAY . " ---------------------------- " . TextFormat::YELLOW . "/left/\n" .
            TextFormat::AQUA . "Welcome back " . TextFormat::GREEN . "@player" . TextFormat::AQUA . "!",
        "auth" => [
            "login" => [
                "join" => TextFormat::YELLOW . "Please type your password in chat to login...",
                "successful" => TextFormat::GREEN . "You have successfully logged in!",
                "failure" => TextFormat::DARK_GRAY . "Oops, something went wrong while logging.\n" . TextFormat::DARK_GRAY . "Please try again...",
                "popup" => TextFormat::GRAY . "Please \"" . TextFormat::AQUA . "Login" . TextFormat::GRAY . "\" to play!"
            ], "register" => [
                "join" => TextFormat::LIGHT_PURPLE . "It appears that this account is not registered,\n but you can register it now!\n" . TextFormat::AQUA . "Please type a password in chat " . TextFormat::YELLOW . "(Nobody will see it)...",
                "successful" => TextFormat::GRAY . "You have been successfully registered!",
                "failure" => TextFormat::DARK_GRAY . "Oops, something went wrong in the registration process.\n" . TextFormat::DARK_GRAY . "Please keep in mind that each e-mail should be UNIQUE.\n" . TextFormat::DARK_GRAY . "Please try again...",
                "popup" => TextFormat::GRAY . "Please \"" . TextFormat::AQUA . "Register" . TextFormat::GRAY . "\" to play!",
                // The following section is for "registration steps" :3
                "steps" => [
                    TextFormat::AQUA . "Please type a password in chat " . TextFormat::YELLOW . "(Nobody will see it)...", // Step 0
                    TextFormat::YELLOW . "Please confirm your new password...", // Step 1
                    TextFormat::YELLOW . "Please enter your e-mail address...", // Step 2
                    TextFormat::YELLOW . "Please confirm your e-mail address...", // Step 3
                ],
                "password" => [
                    "confirm" => TextFormat::GREEN . "Very well! " . TextFormat::YELLOW . "Please confirm your new password...",
                    "invalid" => TextFormat::RED . "You have entered an invalid password!\n" . TextFormat::AQUA . "Please consider the following rule:\n" . TextFormat::YELLOW . "\t- No spaces\n" . TextFormat::AQUA . "Please type another password...",
                    "match" => TextFormat::RED . "No! " . TextFormat::YELLOW . "The passwords doesn't match! Let's start again...\n" . TextFormat::AQUA . "Please type a password...",
                    "success" => TextFormat::GREEN . "Awesome! " . TextFormat::YELLOW . "Now please enter your e-mail address..."
                ], "email" => [
                    "confirm" => TextFormat::GREEN . "Good job! " . TextFormat::YELLOW . "Please confirm your e-mail address...",
                    "invalid" => TextFormat::RED . "Wrong! " . TextFormat::YELLOW . "This e-mail address is invalid!\n" . TextFormat::AQUA . "Please type a valid e-mail...",
                    "match" => TextFormat::RED . "What?! " . TextFormat::YELLOW . "The e-mails doesn't match! Let's try again...\n" . TextFormat::AQUA . "Please type your e-mail...",
                    "success" => TextFormat::GREEN . "Excellent! " . TextFormat::YELLOW . "Please wait a moment while we create your account..."
                ]
            ]
        ], "popups" => [
            TextFormat::LIGHT_PURPLE . ":D " . TextFormat::AQUA . "Have " . TextFormat::GREEN . "Fun" . TextFormat::RED . "! " . TextFormat::LIGHT_PURPLE . " :D"
        ], "kick" => [
            "sub" => "\n" . TextFormat::YELLOW . "Be sure to visit us at:\n" . TextFormat::ITALIC . TextFormat::AQUA . "MinePocket.com" . TextFormat::RESET . TextFormat::YELLOW . "!",
            "notlogged" => TextFormat::YELLOW . "You were kicked because you '" . TextFormat::RED . "Didn't logged in" . TextFormat::YELLOW . "'!",
            "loggedin" => TextFormat::YELLOW . "You were kicked because a player with the same " . TextFormat::ITALIC . "username" . TextFormat::RED . TextFormat::YELLOW . " is already logged in.",
            "advertising" => TextFormat::YELLOW . "You were kicked for '" . TextFormat::RED . "Advertising" . TextFormat::YELLOW . "'!",
            "swear" => TextFormat::YELLOW . "You were kicked for '" . TextFormat::RED . "Swearing" . TextFormat::YELLOW . "'!",
            "banned" => TextFormat::YELLOW . "You where kicked because you're '" . TextFormat::RESET . "Banned" . TextFormat::YELLOW . "'!"
        ]
    ];
    /** @var array */
    private $spanish = [
        "motd" => TextFormat::YELLOW . "/right/" . TextFormat::GRAY . " ---------------------------- " . TextFormat::YELLOW . "/left/\n" .
            TextFormat::YELLOW . "¡Bienvenido a la " . TextFormat::LIGHT_PURPLE . "Red " . TextFormat::AQUA . "MinePocket" . TextFormat::YELLOW . "!\n" .
            TextFormat::YELLOW . "/right/" . TextFormat::GRAY . " ---------------------------- " . TextFormat::YELLOW . "/left/\n" .
            TextFormat::AQUA . "¡Bienvenido " . TextFormat::GREEN . "@player" . TextFormat::AQUA . "!",
        "auth" => [
            "login" => [
                "join" => TextFormat::YELLOW . "Porfavor escribe la contraseña en el chat para continuar...",
                "successful" => TextFormat::GREEN . "¡Sesión iniciada correctamente!",
                "failure" => TextFormat::DARK_GRAY . "Oops, algo salió mal durante el inicio de sesión.\n" . TextFormat::DARK_GRAY . "Porfavor intentalo denuevo...",
                "popup" => TextFormat::GRAY . "¡Porfavor \"" . TextFormat::AQUA . "Inicia Sesión" . TextFormat::GRAY . "\" para jugar!"
            ], "register" => [
                "join" => TextFormat::LIGHT_PURPLE . "Parece que esta cuenta no esta registrada,\n ¡Pero la puedes registrar ahora!\n" . TextFormat::AQUA . "Porfavor escribe una contraseña en el chat " . TextFormat::YELLOW . "(Nadie podrá verla)...",
                "successful" => TextFormat::GRAY . "¡Has sido registrado satisfactoriamente!",
                "failure" => TextFormat::DARK_GRAY . "Oops, algo salió mal durante el registro.\n" . TextFormat::DARK_GRAY . "Porfavor ten en cuenta que cada e-mail debe ser UNICO.\n" . TextFormat::DARK_GRAY . "Poravor intentalo denuevo...",
                "popup" => TextFormat::GRAY . "¡Porfavor \"" . TextFormat::AQUA . "Registrate" . TextFormat::GRAY . "\" para jugar!",
                "steps" => [
                    TextFormat::AQUA . "Porfavor escribe una contraseña en el chat " . TextFormat::YELLOW . "(Nadie podrá verla)...",
                    TextFormat::YELLOW . "Porfavor confirma tu nueva contraseña...",
                    TextFormat::YELLOW . "Porfavor escribe tu e-mail...",
                    TextFormat::YELLOW . "Porfavor confirma tu e-mail...",
                ],
                "password" => [
                    "confirm" => TextFormat::GREEN . "¡Muy bien! " . TextFormat::YELLOW . "Porfavor confirma tu nueva contraseña...",
                    "invalid" => TextFormat::RED . "¡Has introducido una contraseña invalida!\n" . TextFormat::AQUA . "Porfavor considera la siguiente regla:\n" . TextFormat::YELLOW . "\t- No utilizar \"espacios\"\n" . TextFormat::AQUA . "Porfavor escribe otra contraseña...",
                    "match" => TextFormat::RED . "¡No! " . TextFormat::YELLOW . "¡Las contraseñas no concuerdan! Intentemoslo denuevo...\n" . TextFormat::AQUA . "Porfavor escribe una contraseña...",
                    "success" => TextFormat::GREEN . "¡Asombroso! " . TextFormat::YELLOW . "Ahora, porfavor escribe tu e-mail..."
                ], "email" => [
                    "confirm" => TextFormat::GREEN . "¡Buen trabajo! " . TextFormat::YELLOW . "Porfavor confirma tu e-mail...",
                    "invalid" => TextFormat::RED . "¡Mal! " . TextFormat::YELLOW . "¡Este e-mail no es valido!\n" . TextFormat::AQUA . "Porfavor escribe un e-mail valido...",
                    "match" => TextFormat::RED . "¡¿Qué?! " . TextFormat::YELLOW . "¡Los e-mails no concuerdan! Intentemoslo denuevo...\n" . TextFormat::AQUA . "Porfavor escribe tu e-mail...",
                    "success" => TextFormat::GREEN . "¡Excelente! " . TextFormat::YELLOW . "Porfavor espera un momento mientras configuramos tu cuenta..."
                ]
            ]
        ], "popups" => [
            TextFormat::LIGHT_PURPLE . ":D " . TextFormat::RED . "¡" . TextFormat::AQUA . "Diviertete " . TextFormat::GREEN . "Mucho" . TextFormat::RED . "! " . TextFormat::LIGHT_PURPLE . " :D"
        ], "kick" => [
            "sub" => "\n" . TextFormat::YELLOW . "Asegurate de visitarnos en:\n" . TextFormat::ITALIC . TextFormat::AQUA . TextFormat::ITALIC . TextFormat::UNDERLINE . "http://www.MinePocket.com",
            "notlogged" => TextFormat::YELLOW . "¡Has salido del juego debido a que '" . TextFormat::RED . "No iniciaste sesión" . TextFormat::YELLOW . "'!",
            "loggedin" => TextFormat::YELLOW . "Has salido del juego debido a que otro usuario con el mismo " . TextFormat::ITALIC . "nombre" . TextFormat::RESET . TextFormat::YELLOW . " ya ha iniciado sesión.",
            "advertising" => TextFormat::YELLOW . "¡Has salido del juego por '" . TextFormat::RED . "Anunciar sin permiso" . TextFormat::YELLOW . "'!",
            "swear" => TextFormat::YELLOW . "¡Has salido del juego por utilizar palabras '" . TextFormat::RED . "Ofensivas" . TextFormat::YELLOW . "'!",
            "banned" => TextFormat::YELLOW . "¡Has salido del juego debido a que estas '" . TextFormat::RED . "Banneado" . TextFormat::YELLOW . "'!"
        ]
    ];

    /**
     * @param string $message
     * @param string $language
     * @return array|string
     */
    public function getTranslation($message, $language){
        if(substr($message, 0, 1) !== "%"){
            return $message;
        }
        $message = str_replace("%", "", $message);
        $language = $this->identifyLanguage($language);
        $vars = \explode(".", $message);
        $base = \array_shift($vars);
        if(isset($language[$base])){
            $base = $language[$base];
        }
        while(\count($vars) > 0){
            $baseKey = \array_shift($vars);
            if(\is_array($base) and isset($base[$baseKey])){
                $base = $base[$baseKey];
            }
        }
        return $base;
    }

    /**
     * @param string $key
     * @param string $language
     * @return array|bool
     */
    public function getArray($key, $language){
        if(is_array($a = $this->identifyLanguage($language)[$key])){
            return $a;
        }
        return false;
    }

    /**
     * @param array $language
     * @return array
     */
    private function identifyLanguage($language){
        switch(strtolower($language)){
            case "english":
            default:
                $language = "english";
                break;
            case "spanish":
                $language =  "spanish";
                break;
        }
        return $this->{$language};
    }

    /**
     * @param SuperPlayer $player
     * @return string
     */
    public function getPlayerLanguage(SuperPlayer $player){
        if($player->getSession()->getLanguage() === null){
            $this->setPlayerLanguageByCountry($player);
        }
        return $player->getSession()->getLanguage();
    }

    /**
     * @param SuperPlayer $player
     * @return string
     */
    public function getPlayerCountry(SuperPlayer $player){
        if($player->getSession()->getCountry() === null){
            $this->setPlayerCountryByIP($player);
        }
        return $player->getSession()->getCountry();
    }

    /**
     * @param SuperPlayer $player
     */
    public function initPlayer(SuperPlayer $player){
        $this->setPlayerCountryByIP($player);
        $this->setPlayerLanguageByCountry($player);
    }

    /**
     * @param SuperPlayer $player
     */
    private function setPlayerLanguageByCountry(SuperPlayer $player){
        switch(strtoupper($this->getPlayerCountry($player))){
            case "AG":
            case "AI":
            case "AQ":
            case "AS":
            case "AU":
            case "BB":
            case "BW":
            case "CA":
            case "GB":
            case "IE":
            case "KE":
            case "NG":
            case "NZ":
            case "PH":
            case "SG":
            case "US":
            case "ZA":
            case "ZM":
            case "ZW":
            default:
                $l = "english";
                break;
            case "AD":
            case "AR":
            case "BO":
            case "CL":
            case "CO":
            case "CR":
            case "CU":
            case "DO":
            case "EC":
            case "ES":
            case "GT":
            case "HN":
            case "MX":
            case "NI":
            case "PA":
            case "PE":
            case "PR":
            case "PY":
            case "SV":
            case "UY":
            case "VE":
                $l = "spanish";
                break;
        }
        $player->getSession()->setLanguage($l);
    }

    /**
     * @param SuperPlayer $player
     */
    private function setPlayerCountryByIP(SuperPlayer $player){
        if(!filter_var($player->getAddress(), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)){
            $url = "http://ip-api.com/json/";
        }else{
            $url = "http://ip-api.com/json/" . $player->getAddress();
        }
        $json = json_decode(Utils::getURL($url), true);
        if(isset($json["countryCode"])){
            $country = $json["countryCode"];
        }else{
            $country = "UNKNOWN";
        }
        $player->getSession()->setCountry($country);
    }
}
