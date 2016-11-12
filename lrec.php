<?php

/*

#  LEXISML INDEX RECORD (LREC) READER  #

Programmed for use with PHP 5.5--7.1.
Not guaranteed to work with other versions.

##  Usage:  ##

$lrec = new LREC($location);
$lrec = new LREC($source, base);

- $lrec : a newly-created LexisML Index Record object
- location : a URL string pointing to the LREC (must be absolute)
- source : a string in the LREC format
- base : the base URL for the LREC, as a string

*/

//  The LREC_URL ports JavaScript's URL(). It's not perfect but it tries. Note however that it is read-only.

class LREC_URL {

    private $_protocol = "";
    private $_username = "";
    private $_password = "";
    private $_hostname = "";
    private $_port = "";
    private $_pathname = "";
    private $_search = "";
    private $_hash = "";

    private $REGEX = "%^(?:((?:\w*):)(?://(?:([^:]+)(?::(\\S*))?@)?(\\d{1,3}(?:\\.\\d{1,3}){3}|(?:[a-z\x{00a1}-\x{ffff}0-9]\\-*)*[a-z\x{00a1}-\x{ffff}0-9](?:\\.(?:[a-z\x{00a1}-\x{ffff}0-9]\\-*)*[a-z\x{00a1}-\x{ffff}0-9])+\\.?)(?::(\\d{2,5}))?)?)?([^#\\?]*)(\\?[^#]*)?(#.*)?$%iuS";

    function __get($name) {

        switch ($name) {

            case "href":
            if ($this -> _username != "") return $this -> _protocol . "//" . $this -> _username . ":" . $this -> _password . "@" . $this -> _hostname .
            ":" . $this -> _port . $this -> _pathname . $this -> _search . $this -> _hash;
            else return $this -> _protocol . "//" . $this -> _hostname . $this -> _pathname . $this -> _search . $this -> _hash;
            break;

            case "protocol":
            return $this -> _protocol;
            break;

            case "host":
            return $this -> _hostname . ":" . $this -> _port;
            break;

            case "hostname":
            return $this -> _hostname;
            break;

            case "port":
            return $this -> _port;
            break;

            case "pathname":
            return $this -> _pathname;
            break;

            case "search":
            return $this -> _search;
            break;

            case "hash":
            return $this -> _hash;
            break;

            case "username":
            return $this -> _username;
            break;

            case "password":
            return $this -> _password;
            break;

            case "origin":
            return $this -> _protocol . "//" . "@" . $this -> _hostname .
            ":" . $this -> _port;
            break;

        }

    }

    function __set($name, $value) {
        //  Setting not allowed
    }

    function __construct($url, $base = NULL) {
        if (is_null($base)) $base = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}";
        preg_match($this -> REGEX, $base, $base_matches);
        preg_match($this -> REGEX, $url, $matches);
        if ($matches[1] != "") {  //  Protocol is specified
            $this -> _protocol = $matches[1];
            $this -> _username = $matches[2];
            $this -> _password = $matches[3];
            $this -> _hostname = $matches[4];
            $this -> _port = $matches[5];
            $this -> _pathname = $matches[6];
            $this -> _search = $matches[7];
            $this -> _hash = $matches[8];
        }
        else if ($base_matches[1] != "") {  //  Base protocol is specified
            $this -> _protocol = $base_matches[1];
            $this -> _username = $base_matches[2];
            $this -> _password = $base_matches[3];
            $this -> _hostname = $base_matches[4];
            $this -> _port = $base_matches[5];
            if ($matches[6] && $matches[6][0] == "/") {  //  Relative from root directory
                $this -> _pathname = $matches[6];
                $this -> _search = $matches[7];
                $this -> _hash = $matches[8];
            }
            else {
                $this -> _pathname = $base_matches[6] . "/./" . $matches[6];
                $this -> _search = $matches[7];
                $this -> _hash = $matches[8];
            }
        }
        else ;  //  Both were relative; error!
    }

    function toString() {
        if ($this -> _username != "") return $this -> _protocol . "//" . $this -> _username . ":" . $this -> _password . "@" . $this -> _hostname .
        ":" . $this -> _port . $this -> _pathname . $this -> _search . $this -> _hash;
        else return $this -> _protocol . "//" . $this -> _hostname . $this -> _pathname . $this -> _search . $this -> _hash;
    }

}

class LREC {

    //  Loading variables:

    public $src = "";
    public $base = "";
    public $loaded = false;

    //  Record array:

    public $records = array();

    //  Metadata:

    public $title = "";
    public $subtitle = "";
    public $author = "";
    public $date = "";
    public $language = "";
    public $description = "";
    public $splashes = array();
    public $frontmatter = "";

    //  Tags:

    public $tags = array();
    public $groups = array();

    //  Lexemes:

    public $lexemes = array();

    //  Constructor:

    function __construct($arg01, $arg02 = NULL) {

        //  Get source and base URL:

        if (is_null($arg02)) {
            $base_url = new LREC_URL($arg01);
            $this -> base = $base_url -> toString();
            $this -> src = file_get_contents($this -> base);
        }
        else {
            $base_url = new LREC_URL($arg02);
            $this -> base = $base_url -> toString();
            $this -> src = $arg01;
        }

        //  Splits records:

        $recordsrc = explode("\n%%\n", $this -> src);

        //  Loops over each record:

        for ($i = 0; $i < count($recordsrc); $i++) {

            //  Gets the fields of each record:

            $fieldsrc = explode("\n", $recordsrc[$i]);

            //  Creates a new record object and sets up variables:

            $this_record = array();
            $last = NULL;

            //  Loops over each field and loads its data:

            for ($j = 0; $j < count($fieldsrc); $j++) {

                //  Checks to see if the field is actually a comment:

                if ($fieldsrc[$j][0] == "%") continue;

                //  Gets the index of the field delimiter:

                $k = strpos($fieldsrc[$j], " : ");

                //  If the line doesn't match field syntax…:

                if ($k === FALSE) {

                    //  It is either a continuation:

                    if (!is_null($last)) {
                        if (!is_array($this_record[$last])) $this_record[$last] .= " " . trim($fieldsrc[$j]);
                        else $this_record[$l][count($this_record[$l] - 1)] .= " " . trim($fieldsrc[$j]);
                        continue;
                    }

                    //  Or a syntax error:

                    else throw new Exception("LREC Error: Syntax error on line number " . ($j+1) . " of record number " . ($i+1) . ".");

                }

                //  Loads the data into the record:

                $last = strtolower(trim(substr($fieldsrc[$j], 0, $k)));

                if (!isset($this_record[$last])) $this_record[$last] = trim(substr($fieldsrc[$j], $k + 3));
                else if (is_array($this_record[$last])) $this_record[$last][] = trim(substr($fieldsrc[$j], $k + 3));
                else $this_record[$last] = array($this_record[$last], trim(substr($fieldsrc[$j], $k + 3)));

            }

            //  Adds a nonempty record:

            if (count($this_record) != 0) $this -> records[] = $this_record;

        }

        //  Start loading data:

        $i = 0;

        //  Ensures the first record is a metadata record:

        if (!isset($this -> records[$i]) || !isset($this -> records[$i]["title"])) throw new Exception("LREC Error: First record is not a valid metadata record.");

        //  Sets up metadata:

        if (is_array($this -> records[$i]["title"])) throw new Exception("LREC Error: Title is defined twice.");
        $this -> title = $this -> records[$i]["title"];

        if (is_array($this -> records[$i]["subtitle"])) throw new Exception("LREC Error: Subtitle is defined twice.");
        $this -> subtitle = $this -> records[$i]["subtitle"];

        if (is_array($this -> records[$i]["author"])) throw new Exception("LREC Error: Author is defined twice.");
        $this -> author = $this -> records[$i]["author"];

        if (is_array($this -> records[$i]["date"])) throw new Exception("LREC Error: Date is defined twice.");
        $this -> date = $this -> records[$i]["date"];

        if (is_array($this -> records[$i]["language"])) throw new Exception("LREC Error: Language is defined twice.");
        $this -> language = $this -> records[$i]["language"];

        if (is_array($this -> records[$i]["description"])) throw new Exception("LREC Error: Description is defined twice.");
        $this -> description = $this -> records[$i]["description"];

        if (is_array($this -> records[$i]["splash"])) $this -> splashes = $this -> records[$i]["splash"];
        else if (isset($this -> records[$i]["splash"])) $this -> splashes = array($this -> records[$i]["splash"]);

        if (is_array($this -> records[$i]["frontmatter"])) throw new Exception("LREC Error: Frontmatter is defined twice.");
        $this -> frontmatter = $this -> records[$i]["frontmatter"];

        //  Iterates over tag-groups:

        $i++;

        while (isset($this -> records[$i]) && (isset($this -> records[$i]["group"]) || isset($this -> records[$i]["title"]))) {

            //  Ensures there aren't any stray records:

            if ($this -> records[$i]["title"]) throw new Exception("LREC Error: Metadata is defined twice.");

            //  Tag-group error checking:

            if (is_array($this -> records[$i]["group"])) throw new Exception("LREC Error: A tag-group record has multiple group fields.");
            if (isset($this -> groups[$this -> records[$i]["group"]])) throw new Exception("LREC Error: Tag-group '" . $this -> records[$i]["group"] . "' is defined twice.");
            if (!isset($this -> records[$i]["subgroup"]) && !isset($this -> records[$i]["tag"])) throw new Exception("LREC Error: Tag-group '" . $this -> records[$i]["group"] . "' does not contain any tags or subgroups.");
            if (is_array($this -> records[$i]["description"])) throw new Exception("LREC Error: Tag-group" . $this -> records[$i]["group"] . "' contains multiple descriptions.");

            //  Loads tag-group:

            $this -> groups[strtolower($this -> records[$i]["group"])] = array("parent" => NULL, "description" => $this -> records[$i]["description"]);

            //  Iterates over subgroups:

            if (is_array($this -> records[$i]["subgroup"])) {

                for ($j = 0; $j < count($this -> records[$i]["subgroup"]); $j++) {

                    //  Subgroup error checking:

                    if (!isset($this -> groups[strtolower($this -> records[$i]["subgroup"][$j])])) throw new Exception("LREC Error: Tag-group '" . strtolower($this -> records[$i]["subgroup"][$j]) . "' is not defined.");
                    else if (!is_null($this -> groups[strtolower($this -> records[$i]["subgroup"][$j])]["parent"])) throw new Exception("LREC Error: Tag-group '" . strtolower($this -> records[$i]["subgroup"][$j]) . "' is a subgroup of multiple groups.");

                    //  Subgroup loading:

                    $this -> groups[strtolower($this -> records[$i]["subgroup"][$j])]["parent"] = strtolower($this -> records[$i]["group"]);

                }

            }

            else if (isset($this -> records[$i]["subgroup"])) {

                //  Subgroup error checking:

                if (!isset($this -> groups[strtolower($this -> records[$i]["subgroup"])])) throw new Exception("LREC Error: Tag-group '" . strtolower($this -> records[$i]["subgroup"]) . "' is not defined.");
                else if (!is_null($this -> groups[strtolower($this -> records[$i]["subgroup"])]["parent"])) throw new Exception("LREC Error: Tag-group '" . strtolower($this -> records[$i]["subgroup"]) . "' is a subgroup of multiple groups.");

                //  Subgroup loading:

                $this -> groups[strtolower($this -> records[$i]["subgroup"])]["parent"] = strtolower($this -> records[$i]["group"]);

            }

            //  Iterates over tags:

            if (is_array($this -> records[$i]["tag"])) {

                for ($j = 0; $j < count($this -> records[$i]["tag"]); $j++) {

                    //  Tag error checking:

                    if (isset($this -> tags[strtolower($this -> records[$i]["tag"][$j])]) && !is_null($this -> tags[strtolower($this -> records[$i]["tag"][$j])]["parent"])) throw new Exception("LREC Error: Tag '" . strtolower($this -> records[$i]["tag"][$j]) . "' is in multiple groups.");

                    //  Tag loading:

                    $this -> tags[strtolower($this -> records[$i]["tag"][$j])] = array("parent" => strtolower($this -> records[$i]["group"]));

                }

            }

            else if (isset($this -> records[$i]["tag"])) {

                //  Tag error checking:

                if (isset($this -> tags[strtolower($this -> records[$i]["tag"])]) && !is_null($this -> tags[strtolower($this -> records[$i]["tag"])]["parent"])) throw new Exception("LREC Error: Tag '" . strtolower($this -> records[$i]["tag"]) . "' is in multiple groups.");

                //  Tag loading:

                $this -> tags[strtolower($this -> records[$i]["tag"])] = array("parent" => strtolower($this -> records[$i]["group"]));

            }

            $i++;

        }

        //  Iterates over remaining records:

        while (isset($this -> records[$i])) {

            //  Ensures there aren't any stray records:

            if ($this -> records[$i]["title"]) throw new Exception("LREC Error: Metadata is defined twice.");
            if ($this -> records[$i]["group"]) throw new Exception("LREC Error: Tag-group records must come directly after metadata.");

            //  Lexeme handling:

            if (isset($this -> records[$i]["lexeme"])) {

                //  Lexeme error checking:

                if (is_array($this -> records[$i]["lexeme"])) throw new Exception("LREC Error: A lexeme record has multiple lexeme fields.");
                if (isset($this -> lexemes[$this -> records[$i]["lexeme"]])) throw new Exception("LREC Error: Lexeme '" . $this -> records[$i]["lexeme"] . "' is defined twice.");

                //  Sets up variables:

                $this -> lexemes[$this -> records[$i]["lexeme"]] = array(
                    "url" => NULL,
                    "language" => NULL,
                    "pronunciation" => array(),
                    "tags" => array(),
                    "gloss" => "",
                    "inflections" => array(),
                    "alternates" => array()
                );

                //  Loads fields:

                if (is_array($this -> records[$i]["at"])) throw new Exception("LREC Error: Lexeme '" . $this -> records[$i]["lexeme"] . "' has two at fields.");
                else if (isset($this -> records[$i]["at"])) $this -> lexemes[$this -> records[$i]["lexeme"]]["url"] = $this -> records[$i]["at"];
                else throw new Exception("LREC Error: Lexeme '" . $this -> records[$i]["lexeme"] . "' has no at field.");

                if (is_array($this -> records[$i]["language"])) throw new Exception("LREC Error: Lexeme '" . $this -> records[$i]["lexeme"] . "' has two language fields.");
                else if (isset($this -> records[$i]["language"])) $this -> lexemes[$this -> records[$i]["lexeme"]]["language"] = $this -> records[$i]["language"];

                if (is_array($this -> records[$i]["pronunciation"])) $this -> lexemes[$this -> records[$i]["lexeme"]]["pronunciation"] = $this -> records[$i]["pronunciation"];
                else if (isset($this -> records[$i]["pronunciation"])) $this -> lexemes[$this -> records[$i]["lexeme"]]["pronunciation"] = array($this -> records[$i]["pronunciation"]);

                if (is_array($this -> records[$i]["tagged"])) $this -> lexemes[$this -> records[$i]["lexeme"]]["tags"] = $this -> records[$i]["tagged"];
                else if (isset($this -> records[$i]["tagged"])) $this -> lexemes[$this -> records[$i]["lexeme"]]["tags"] = array($this -> records[$i]["tagged"]);

                if (is_array($this -> records[$i]["gloss"])) throw new Exception("LREC Error: Lexeme '" . $this -> records[$i]["lexeme"] . "' has two language fields.");
                else if (isset($this -> records[$i]["gloss"])) $this -> lexemes[$this -> records[$i]["lexeme"]]["gloss"] = $this -> records[$i]["gloss"];

                //  Normalizes tags and records if necessary:

                for ($j = 0; $j < count($this -> lexemes[$this -> records[$i]["lexeme"]]["tags"]); $j++) {
                    $this -> lexemes[$this -> records[$i]["lexeme"]]["tags"][$j] = strtolower($this -> lexemes[$this -> records[$i]["lexeme"]]["tags"][$j]);
                    if (!isset($this -> tags[$this -> lexemes[$this -> records[$i]["lexeme"]]["tags"][$j]])) $this -> tags[$this -> lexemes[$this -> records[$i]["lexeme"]]["tags"][$j]] = array("parent" => NULL);
                }

            }

            //  Inflection handling:

            else if (isset($this -> records[$i]["inflected"])) {

                //  Inflection error checking:

                if (is_array($this -> records[$i]["inflected"])) throw new Exception("LREC Error: An inflection record has multiple inflection fields.");
                if (!isset($this -> records[$i]["of"])) throw new Exception("LREC Error: Inflection '" . $this -> records[$i]["inflected"] . "' has no of field.");
                if (is_array($this -> records[$i]["of"])) throw new Exception("LREC Error: Inflection '" . $this -> records[$i]["inflected"] . "' has two of fields.");
                if (!isset($this -> lexemes[$this -> records[$i]["of"]])) throw new Exception("LREC Error: Inflection '" . $this -> records[$i]["inflected"] . "' points to a lexeme which does not exist ('" . $this -> records[$i]["of"] . "').");
                if (isset($this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["inflected"]])) throw new Exception("LREC Error: Inflection '" . $this -> records[$i]["inflected"] . "' of lexeme '" . $this -> records[$i]["of"] . "' is defined twice.");

                //  Sets up variables:

                $this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["inflected"]] = array(
                    "pronunciation" => array(),
                    "alternates" => array()
                );

                //  Loads fields:

                if (is_array($this -> records[$i]["pronunciation"])) $this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["inflected"]]["pronunciation"] = $this -> records[$i]["pronunciation"];
                else if (isset($this -> records[$i]["pronunciation"])) $this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["inflected"]]["pronunciation"] = array($this -> records[$i]["pronunciation"]);
                else $this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["inflected"]]["pronunciation"] = $this -> lexemes[$this -> records[$i]["of"]]["pronunciation"];

            }

            //  Alternate handling:

            else if (isset($this -> records[$i]["alternate"])) {

                //  Alternate error checking:

                if (is_array($this -> records[$i]["alternate"])) throw new Exception("LREC Error: An alternate record has multiple inflection fields.");
                if (!isset($this -> records[$i]["for"])) throw new Exception("LREC Error: Alternate '" . $this -> records[$i]["alternate"] . "' has no for field.");
                if (is_array($this -> records[$i]["of"])) throw new Exception("LREC Error: Alternate '" . $this -> records[$i]["alternate"] . "' has two of fields.");
                if (is_array($this -> records[$i]["for"])) throw new Exception("LREC Error: Alternate '" . $this -> records[$i]["alternate"] . "' has two for fields.");

                //  Lexeme alternates:

                if (!isset($this -> records[$i]["of"])) {

                    //  More error handling:

                    if (!isset($this -> lexemes[$this -> records[$i]["for"]])) throw new Exception("LREC Error: Alternate '" . $this -> records[$i]["alternate"] . "' points to a lexeme which does not exist ('" . $this -> records[$i]["for"] . "').");
                    if (isset($this -> lexemes[$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]])) throw new Exception("LREC Error: Alternate '" . $this -> records[$i]["alternate"] . "' for lexeme '" . $this -> records[$i]["for"] . "' is defined twice.");

                    //  Sets up variables:

                    $this -> lexemes[$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]] = array(
                        "script" => NULL,
                        "pronunciation" => NULL
                    );

                    //  Loads fields:

                    if (is_array($this -> records[$i]["script"])) throw new Exception("LREC Error: Alternate '" . $this -> records[$i]["alternate"] . "' for lexeme '" . $this -> records[$i]["for"] . "' has two script fields.");
                    else $this -> lexemes[$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]]["script"] = $this -> records[$i]["script"];


                    if (is_array($this -> records[$i]["pronunciation"])) $this -> lexemes[$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]]["pronunciation"] = $this -> records[$i]["pronunciation"];
                    else if (isset($this -> records[$i]["pronunciation"])) $this -> lexemes[$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]]["pronunciation"] = array($this -> records[$i]["pronunciation"]);
                    else $this -> lexemes[$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]]["pronunciation"] = $this -> lexemes[$this -> records[$i]["for"]]["pronunciation"];

                }

                //  Inflection alternates:

                else {

                    //  More error handling:

                    if (!isset($this -> lexemes[$this -> records[$i]["of"]])) throw new Exception("LREC Error: Alternate '" . $this -> records[$i]["alternate"] . "' points to a lexeme which does not exist ('" . $this -> records[$i]["of"] . "').");
                    if (!isset($this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["for"]])) throw new Exception("LREC Error: Alternate '" . $this -> records[$i]["alternate"] . "' points to an inflection which does not exist ('" . $this -> records[$i]["for"] . "' of lexeme '" . $this -> records[$i]["of"] . "').");
                    if (isset($this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]])) throw new Exception("LREC Error: Alternate '" . $this -> records[$i]["alternate"] . "' for inflection '" . $this -> records[$i]["for"] . "' of lexeme '" . $this -> records[$i]["of"] . "' is defined twice.");

                    //  Sets up variables:

                    $this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]] = array(
                        "script" => NULL,
                        "pronunciation" => NULL
                    );

                    //  Loads fields:

                    if (is_array($this -> records[$i]["script"])) throw new Exception("LREC Error: Alternate '" . $this -> records[$i]["alternate"] . "' for inflection '" . $this -> records[$i]["for"] . "' of lexeme '" . $this -> records[$i]["of"] . "' has two script fields.");
                    else $this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]]["script"] = $this -> records[$i]["script"];


                    if (is_array($this -> records[$i]["pronunciation"])) $this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]]["pronunciation"] = $this -> records[$i]["pronunciation"];
                    else if (isset($this -> records[$i]["pronunciation"])) $this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]]["pronunciation"] = array($this -> records[$i]["pronunciation"]);
                    else $this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["for"]]["alternates"][$this -> records[$i]["alternate"]]["pronunciation"] = $this -> lexemes[$this -> records[$i]["of"]]["inflections"][$this -> records[$i]["for"]]["pronunciation"];

                }

            }

            else throw new Exception("LREC Error: Record not recognized.");

            $i++;

        }

        $this -> loaded = TRUE;

    }

}
