<?php

/*

#  GENERALLY EASY LEDGER (GEL) PARSER  #

Programmed for use with PHP 5.5--7.1.
Not guaranteed to work with other versions.

##  Usage:  ##

$gel = new GEL($location);

- $gel : a newly-created Generally Easy Ledger object
- location : a URL string pointing to the GEL (must be absolute)

*/

class GEL {

    //  Loading variables:

    public $source = "";
    public $location = "";
    public $loaded = false;

    //  Record array:

    public $records = array();

    //  Constructor:

    function __construct($location) {

        //  Get source and base URL:

        $this -> location = $location;
        $this -> src = file_get_contents($location);

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

                //  Gets the indentation of the field:

                if ($k !== FALSE) {

                    $n = 0;
                    while ($fieldsrc[$j][$n] == " ") $n++;

                    //  If this is the first field in the record, we store this value:

                    if ($last === NULL) $m = $n;

                }

                //  If the line doesn't match field syntax…:

                if ($k === FALSE || $m !== $n) {

                    //  It is either a continuation:

                    if (!is_null($last)) {
                        if (!is_array($this_record[$last])) $this_record[$last] .= "\n" . trim($fieldsrc[$j]);
                        else $this_record[$l][count($this_record[$l] - 1)] .= "\n" . trim($fieldsrc[$j]);
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

        $this -> loaded = TRUE;

    }

}
