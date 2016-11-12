/*

#  GENERALLY EASY LEDGER (GEL) PARSER  #

##  Usage:  ##

    var gel = new GEL(location);
    var gel = new GEL(locationURL);

- gel : a newly-created Generally Easy Ledger object
- location : a URL string pointing to the GEL
- locationURL : a URL object pointing to the GEL

*/

GEL = (function () {

    "use strict";

    function GEL (location) {

        //  Set up variables:

        this.location = undefined
        this.source = undefined;
        this.request = undefined;
        this.loaded = false;

        this.records = [];

        //  Check variable types:

        if (!(typeof location === "string" || location instanceof String || location instanceof URL)) {
            throw new Error("GEL Error: Parameter 1 is not of proper type.");
            return;
        }

        //  Get source URL and make a request/load:

        this.location = (location instanceof URL) ? location.href : location;

        this.request = new XMLHttpRequest();
        this.request.gel = this;
        this.request.addEventListener("load", function () {this.gel.load();}, false);
        this.request.open("GET", this.location);
        this.request.responseType = "text";
        this.request.overrideMimeType("text/plain");
        this.request.send();

    }

    GEL.prototype = (Object.freeze !== undefined ? Object.freeze : function (n) {return n;})({

        load: function () {

            //  Makes sure there's something to load:

            if (!this.source && !this.request) {
                throw new Error("GEL Error: Attempted to load, but no source code was found and no request was made.");
                return;
            }

            //  Loads from request, if applicable:

            if (this.request) this.source = this.request.responseText;

            //  Variable setup:

            var recordsrc = this.source.split("\n%%\n");
            var fieldsrc;
            var i;
            var j;
            var k;
            var l;
            var m;
            var n;
            var this_record;

            var load_event = window.CustomEvent ? new CustomEvent("GEL_Loaded", {detail: {gel: this}}) : null;

            //  Loops over each record and loads its data:

            for (i = 0; i < recordsrc.length; i++) {

                //  Gets the fields of the record:

                fieldsrc = recordsrc[i].split("\n");

                //  Creates a new record object and sets up variables:

                this.records.push(this_record = {});

                l = undefined;

                //  Loops over each field and loads its data:

                for (j = 0; j < fieldsrc.length; j++) {

                    //  Checks to see if the field is actually a comment:

                    if (fieldsrc[j][0] === "%") continue;

                    //  Gets the index of the field delimiter:

                    k = fieldsrc[j].indexOf(" : ");

                    //  Gets the indentation of the field:

                    if (k !== -1) {

                        n = 0;
                        while (fieldsrc[j].charAt(n) === " ") {
                            n++;
                        }

                        //  If this is the first field in the record, we store this value:

                        if (l === undefined) m = n;

                    }

                    //  If the line doesn't match field syntax…:

                    if (k === -1 || m !== n) {

                        //  It is either a continuation:

                        if (l !== undefined) {
                            if (!Array.isArray(this_record[l])) this_record[l] += "\n" + fieldsrc[j].trim();
                            else this_record[l][this_record[l].length-1] += "\n" + fieldsrc[j].trim();
                            continue;
                        }

                        //  Or a syntax error:

                        else {
                            throw new Error("GEL Error: Syntax error on line number " + (j+1) + " of record number " + (i+1) + ".");
                            continue;
                        }

                    }

                    //  Otherwise, we load the data into the record:

                    l = fieldsrc[j].substr(0, k).trim();

                    if (this_record[l] === undefined) this_record[l] = fieldsrc[j].substr(k+3).trim();
                    else if (Array.isArray(this_record[l])) this_record[l].push(fieldsrc[j].substr(k+3).trim());
                    else this_record[l] = [this_record[l], fieldsrc[j].substr(k+3).trim()];

                }

                //  Pops an empty record:

                k = false;
                for (j in this_record) {
                    if (this_record.hasOwnProperty(j)) {
                        k = true;
                        break;
                    }
                }
                if (!k) {
                    this.records.pop();
                    continue;
                }

            }

            this.loaded = true;
            if (load_event) document.dispatchEvent(load_event);

        }

    });

    return GEL;

})();
