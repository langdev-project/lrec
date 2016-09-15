/*

#  LEXISML INDEX RECORD (LREC) READER  #

Requires support for the URL() constructor.

##  Usage:  ##

    var lrec = new LREC(location);
    var lrec = new LREC(locationURL);
    var lrec = new LREC(source, base);
    var lrec = new LREC(source, baseURL);

- lrec : a newly-created LexisML Index Record object
- location : a URL string pointing to the LREC
- locationURL : a URL object pointing to the LREC
- source : a string in the LREC format
- base : the base URL for the LREC, as a string
- baseURL : the base URL for the LREC, as a URL object

*/

LREC = (function () {

    "use strict";

    function LREC (var1, var2) {

        //  Check variable types:

        if (!(typeof var1 === "string" || var1 instanceof String || var1 instanceof URL)) {
            throw new Error("LREC Error: Parameter 1 is not of proper type.");
            return;
        }
        if (var2 !== undefined && !(typeof var2 === "string" || var2 instanceof String || var2 instanceof URL)) {
            throw new Error("LREC Error: Parameter 2 is not of proper type.");
            return;
        }

        //  Set up variables:

        this.src = undefined;
        this.base = undefined;
        this.request = undefined;
        this.loaded = false;

        this.records = [];

        this.title = undefined;
        this.subtitle = undefined;
        this.author = undefined;
        this.date = undefined;
        this.language = undefined;
        this.description = undefined;
        this.splashes = [];
        this.frontmatter = undefined;

        this.tags = {};
        this.groups = {};

        this.lexemes = {};

        //  Get source URL and make a request/load:

        if (var2 === undefined) {

            if (var1 instanceof URL) this.base = var1;
            else this.base = new URL(var1, document.baseURI);

            this.request = new XMLHttpRequest();
            this.request.lrec = this;
            this.request.addEventListener("load", function () {this.lrec.load();}, false);
            this.request.open("GET", this.base.href);
            this.request.responseType = "text";
            this.request.overrideMimeType("text/plain");
            this.request.send();

        }

        else {
            this.src = String(var1);
            if (var2 instanceof URL) this.base = var2;
            else this.base = new URL(var2, document.baseURI);
            this.load();
        }

    }

    LREC.prototype = (Object.freeze !== undefined ? Object.freeze : function (n) {return n;})({

        load: function () {

            //  Makes sure there's something to load:

            if (!this.src && !this.request) {
                console.log("LREC Error: Attempted to load, but no source code was found and no request was made.");
                return;
            }

            //  Loads from request, if applicable:

            if (this.request) this.src = this.request.responseText;

            //  Variable setup:

            var recordsrc = this.src.split("\n%%\n");
            var fieldsrc;
            var i;
            var j;
            var k;
            var l;
            var this_record;

            var load_event = new CustomEvent("LREC_Loaded", {detail: {lrec: this}});

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

                    //  If the line doesn't match field syntax…:

                    if (k === -1) {

                        //  It is either a continuation:

                        if (l !== undefined) {
                            if (!Array.isArray(this_record[l])) this_record[l] += " " + fieldsrc[j].trim();
                            else this_record[l][this_record[l].length-1] += " " + fieldsrc[j].trim();
                            continue;
                        }

                        //  Or a syntax error:

                        else {
                            console.log("LREC Error: Syntax error on line number " + (j+1) + " of record number " + (i+1) + ".");
                            continue;
                        }

                    }

                    //  Loads the data into the record:

                    l = fieldsrc[j].substr(0, k).trim().toLowerCase();

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

            //  Start loading data:

            i = 0;

            //  Ensures the first record is a metadata record:

            if (!this.records[i] || !this.records[i].title) {
                console.log("LREC Error: First record is not a valid metadata record.");
                return;
            }

            //  Sets up metadata:

            if (Array.isArray(this.records[i].title)) {
                console.log("LREC Error: Title is defined twice.");
                this.title = this.records[i].title[0];
            }
            else this.title = this.records[i].title;

            if (Array.isArray(this.records[i].subtitle)) {
                console.log("LREC Error: Subtitle is defined twice.");
                this.subtitle = this.records[i].subtitle[0];
            }
            else this.subtitle = this.records[i].subtitle;

            if (Array.isArray(this.records[i].author)) {
                console.log("LREC Error: Author is defined twice.");
                this.author = this.records[i].author[0];
            }
            else this.author = this.records[i].author;

            if (Array.isArray(this.records[i].date)) {
                console.log("LREC Error: Date is defined twice.");
                this.date = this.records[i].date[0];
            }
            else this.date = this.records[i].date;

            if (Array.isArray(this.records[i].language)) {
                console.log("LREC Error: Language is defined twice.");
                this.language = this.records[i].language[0];
            }
            else this.language = this.records[i].language;

            if (Array.isArray(this.records[i].description)) {
                console.log("LREC Error: Description is defined twice.");
                this.description = this.records[i].description[0];
            }
            else this.description = this.records[i].description;

            if (Array.isArray(this.records[i].splash)) this.splashes = this.records[i].splash.slice();
            else if (this.records[i].splash) this.splashes = [this.records[i].splash];
            else this.splashes = [];

            if (Array.isArray(this.records[i].frontmatter)) {
                console.log("LREC Error: Frontmatter is defined twice.");
                this.frontmatter = new URL(this.records[i].frontmatter[0]);
            }
            else if (this.records[i].frontmatter) this.frontmatter = new URL(this.records[i].frontmatter, this.base);
            else this.frontmatter = undefined;

            //  Iterates over tag-groups:

            while (++i && this.records[i] && (this.records[i].group || this.records[i].title)) {

                //  Ensures there aren't any stray records:

                if (this.records[i].title) {
                    console.log("LREC Error: Metadata is defined twice.")
                    continue;
                }

                //  Tag-group error checking:

                if (Array.isArray(this.records[i].group)) {
                    console.log("LREC Error: A tag-group record has multiple group fields.");
                    continue;
                }
                if (this.groups[this.records[i].group]) {
                    console.log("LREC Error: Tag-group '" + this.records[i].group + "' is defined twice.");
                    continue;
                }
                if (!this.records[i].subgroup && !this.records[i].tag) {
                    console.log("LREC Error: Tag-group '" + this.records[i].group + "' does not contain any tags or subgroups.");
                    continue;
                }

                //  Loads tag-group:

                this.groups[this.records[i].group.toLowerCase()] = {parent: undefined, description: undefined};

                if (Array.isArray(this.records[i].description)) {
                    console.log("LREC Error: Tag-group '" + this.records[i].group + "' contains multiple descriptions.");
                    this.groups[this.records[i].group.toLowerCase()].description = this.records[i].description[0];
                }
                else this.groups[this.records[i].group.toLowerCase()].description = this.records[i].description;

                //  Iterates over subgroups:

                if (Array.isArray(this.records[i].subgroup)) {

                    for (j = 0; j < this.records[i].subgroup.length; j++) {

                        //  Subgroup error checking:

                        if (this.groups[this.records[i].subgroup[j].toLowerCase()] === undefined) {
                            console.log("LREC Error: Tag-group '" + this.records[i].subgroup[j].toLowerCase() + "' is not defined.");
                            continue;
                        }
                        else if (this.groups[this.records[i].subgroup[j].toLowerCase()].parent) {
                            console.log("LREC Error: Tag-group '" + this.records[i].subgroup[j].toLowerCase() + "' is a subgroup of multiple groups.");
                            continue;
                        }

                        //  Subgroup loading:

                        this.groups[this.records[i].subgroup[j].toLowerCase()].parent = this.records[i].group.toLowerCase();

                    }

                }

                else if (this.records[i].subgroup) {

                    //  Subgroup error checking:

                    if (this.groups[this.records[i].subgroup.toLowerCase()] === undefined) console.log("LREC Error: Tag-group '" + this.records[i].subgroup.toLowerCase() + "' is not defined.");
                    else if (this.groups[this.records[i].subgroup.toLowerCase()].parent) console.log("LREC Error: Tag-group '" + this.records[i].subgroup.toLowerCase() + "' is a subgroup of multiple groups.");

                    //  Subgroup loading:

                    this.groups[this.records[i].subgroup.toLowerCase()].parent = this.records[i].group.toLowerCase();

                }

                //  Iterates over tags:

                if (Array.isArray(this.records[i].tag)) {

                    for (j = 0; j < this.records[i].tag.length; j++) {

                        //  Tag error checking:

                        if (this.tags[this.records[i].tag[j].toLowerCase()] && this.tags[this.records[i].tag[j].toLowerCase()].parent) {
                            console.log("LREC Error: Tag '" + this.records[i].subgroup[j].toLowerCase() + "' is in multiple groups.");
                            continue;
                        }

                        //  Tag loading:

                        this.tags[this.records[i].tag[j].toLowerCase()] = {parent: this.records[i].group.toLowerCase()};

                    }

                }

                else if (this.records[i].tag) {

                    //  Tag error checking:

                    if (this.groups[this.records[i].tag.toLowerCase()] && this.groups[this.records[i].tag.toLowerCase()].parent) console.log("LREC Error: Tag '" + this.records[i].subgroup + "' is in multiple groups.");

                    //  Tag loading:

                    this.groups[this.records[i].tag.toLowerCase()] = {parent: this.records[i].group.toLowerCase()};

                }

            }

            //  The last record wasn't processed, so reduce i:

            i--;

            //  Iterates over remaining records:

            while (this.records[++i]) {

                //  Ensures there aren't any stray records:

                if (this.records[i].title) {
                    console.log("LREC Error: Metadata is defined twice.");
                    continue;
                }
                if (this.records[i].group) {
                    console.log("LREC Error: Tag-group records must come directly after metadata.");
                    continue;
                }

                //  Lexeme handling:

                if (this.records[i].lexeme) {

                    //  Lexeme error checking:

                    if (Array.isArray(this.records[i].lexeme)) {
                        console.log("LREC Error: A lexeme record has multiple lexeme fields.");
                        continue;
                    }
                    if (this.lexemes[this.records[i].lexeme]) {
                        console.log("LREC Error: Lexeme '" + this.records[i].lexeme + "' is defined twice.");
                        continue;
                    }

                    //  Sets up variables:

                    this.lexemes[this.records[i].lexeme] = {
                        url: undefined,
                        language: undefined,
                        pronunciation: [],
                        tags: [],
                        gloss: "",
                        inflections: {},
                        alternates: {}
                    }

                    //  Loads fields:

                    if (Array.isArray(this.records[i].at)) {
                        console.log("LREC Error: Lexeme '" + this.records[i].lexeme + "' has two at fields.");
                        this.lexemes[this.records[i].lexeme].url = new URL(this.records[i].at[0]);
                    }
                    else if (this.records[i].at) this.lexemes[this.records[i].lexeme].url = new URL(this.records[i].at, this.base);
                    else {
                        console.log("LREC Error: Lexeme '" + this.records[i].lexeme + "' has no at field.");
                        this.lexemes[this.records[i].lexeme].url = new URL("about:blank");
                    }

                    if (Array.isArray(this.records[i].language)) {
                        console.log("LREC Error: Lexeme '" + this.records[i].lexeme + "' has two language fields.");
                        this.lexemes[this.records[i].lexeme].language = this.records[i].language[0];
                    }
                    else this.lexemes[this.records[i].lexeme].language = this.records[i].language;

                    if (Array.isArray(this.records[i].pronunciation)) this.lexemes[this.records[i].lexeme].pronunciation = this.records[i].pronunciation.slice()
                    else if (this.records[i].pronunciation) this.lexemes[this.records[i].lexeme].pronunciation = [this.records[i].pronunciation];
                    else this.lexemes[this.records[i].lexeme].pronunciation = [];

                    if (Array.isArray(this.records[i].tagged)) this.lexemes[this.records[i].lexeme].tags = this.records[i].tagged.slice();
                    else if (this.records[i].tagged) this.lexemes[this.records[i].lexeme].tags = [this.records[i].tagged];
                    else this.lexemes[this.records[i].lexeme].tags = [];

                    if (Array.isArray(this.records[i].gloss)) {
                        console.log("LREC Error: Lexeme '" + this.records[i].lexeme + "' has two gloss fields.");
                        this.lexemes[this.records[i].lexeme].gloss = String(this.records[i].gloss[0]);
                    }
                    else if (this.records[i].gloss) this.lexemes[this.records[i].lexeme].gloss = String(this.records[i].gloss);

                    //  Normalizes tags and records if necessary:

                    for (j = 0; j < this.lexemes[this.records[i].lexeme].tags.length; j++) {
                        this.lexemes[this.records[i].lexeme].tags[j] = this.lexemes[this.records[i].lexeme].tags[j].toLowerCase();
                        if (typeof this.tags[this.lexemes[this.records[i].lexeme].tags[j].toLowerCase()] === undefined) this.tags[this.lexemes[this.records[i].lexeme].tags[j].toLowerCase()] = {parent: undefined};
                    }

                }

                //  Inflection handling:

                else if (this.records[i].inflected) {

                    //  Inflection error checking:

                    if (Array.isArray(this.records[i].inflected)) {
                        console.log("LREC Error: An inflection record has multiple inflection fields.");
                        continue;
                    }
                    if (!this.records[i].of) {
                        console.log("LREC Error: Inflection '" + this.records[i].inflected + "' has no of field.");
                    }
                    if (Array.isArray(this.records[i].of)) {
                        console.log("LREC Error: Inflection '" + this.records[i].inflected + "' has two of fields.");
                        continue;
                    }
                    if (!this.lexemes[this.records[i].of]) {
                        console.log("LREC Error: Inflection '" + this.records[i].inflected + "' points to a lexeme which does not exist ('" + this.records[i].of + "').");
                        continue;
                    }
                    if (this.lexemes[this.records[i].of].inflections[this.records[i].inflected]) {
                        console.log("LREC Error: Inflection '" + this.records[i].inflected + "' of lexeme '" + this.records[i].of + "' is defined twice.");
                        continue;
                    }

                    //  Sets up variables:

                    this.lexemes[this.records[i].of].inflections[this.records[i].inflected] = {
                        pronunciation: [],
                        alternates: {}
                    }

                    //  Loads fields:

                    if (Array.isArray(this.records[i].pronunciation)) this.lexemes[this.records[i].of].inflections[this.records[i].inflected].pronunciation = this.records[i].pronunciation.slice()
                    else if (this.records[i].pronunciation) this.lexemes[this.records[i].of].inflections[this.records[i].inflected].pronunciation = [this.records[i].pronunciation];
                    else this.lexemes[this.records[i].of].inflections[this.records[i].inflected].pronunciation = this.lexemes[this.records[i].of].pronunciation;

                }

                //  Alternate handling:

                else if (this.records[i].alternate) {

                    //  Alternate error checking:

                    if (Array.isArray(this.records[i].alternate)) {
                        console.log("LREC Error: An alternate record has multiple alternate fields.");
                        continue;
                    }
                    if (!this.records[i].for) {
                        console.log("LREC Error: Alternate '" + this.records[i].alternate + "' has no for field.");
                    }
                    if (Array.isArray(this.records[i].of)) {
                        console.log("LREC Error: Alternate '" + this.records[i].alternate + "' has two of fields.");
                        continue;
                    }
                    if (Array.isArray(this.records[i].for)) {
                        console.log("LREC Error: Alternate '" + this.records[i].alternate + "' has two for fields.");
                        continue;
                    }

                    //  Lexeme alternates:

                    if (!this.records[i].of) {

                        //  More error handling:

                        if (!this.lexemes[this.records[i].for]) {
                            console.log("LREC Error: Alternate '" + this.records[i].alternate + "' points to a lexeme which does not exist ('" + this.records[i].for + "').");
                            continue;
                        }
                        if (this.lexemes[this.records[i].for].alternates[this.records[i].alternate]) {
                            console.log("LREC Error: Alternate '" + this.records[i].alternate + "' for lexeme '" + this.records[i].for + "' is defined twice.");
                            continue;
                        }

                        //  Sets up variables:

                        this.lexemes[this.records[i].for].alternates[this.records[i].alternate] = {
                            script: undefined,
                            pronunciation: undefined
                        }

                        //  Loads fields:

                        if (Array.isArray(this.records[i].script)) {
                            console.log("LREC Error: Alternate '" + this.records[i].alternate + "' for lexeme '" + this.records[i].for + "' has two script fields.");
                            this.lexemes[this.records[i].for].alternates[this.records[i].alternate].script = this.records[i].script[0];
                        }
                        else this.lexemes[this.records[i].for].alternates[this.records[i].alternate].script = this.records[i].script;


                        if (Array.isArray(this.records[i].pronunciation)) this.lexemes[this.records[i].for].alternates[this.records[i].alternate].pronunciation = this.records[i].pronunciation.slice()
                        else if (this.records[i].pronunciation) this.lexemes[this.records[i].for].alternates[this.records[i].alternate].pronunciation = [this.records[i].pronunciation];
                        else this.lexemes[this.records[i].for].alternates[this.records[i].alternate].pronunciation = this.lexemes[this.records[i].for].pronunciation;

                    }

                    //  Inflection alternates:

                    else {

                        //  More error handling:

                        if (!this.lexemes[this.records[i].of]) {
                            console.log("LREC Error: Alternate '" + this.records[i].alternate + "' points to a lexeme which does not exist ('" + this.records[i].of + "').");
                            continue;
                        }
                        if (!this.lexemes[this.records[i].of].inflections[this.records[i].for]) {
                            console.log("LREC Error: Alternate '" + this.records[i].alternate + "' points to an inflection which does not exist ('" + this.records[i].for + "' of lexeme '" + this.records[i].of + "').");
                            continue;
                        }
                        if (this.lexemes[this.records[i].of].inflections[this.records[i].for].alternates[this.records[i].alternate]) {
                            console.log("LREC Error: Alternate '" + this.records[i].alternate + "' for inflection '" + this.records[i].for + "' of lexeme '" + this.records[i].of + "' is defined twice.");
                            continue;
                        }

                        //  Sets up variables:

                        this.lexemes[this.records[i].of].inflections[this.records[i].for].alternates[this.records[i].alternate] = {
                            script: undefined,
                            pronunciation: undefined
                        }

                        //  Loads fields:

                        if (Array.isArray(this.records[i].script)) {
                            console.log("LREC Error: Alternate '" + this.records[i].alternate + "' of inflection '" + this.records[i].for + "' of lexeme '" + this.records[i].of + "' has two script fields.");
                            this.lexemes[this.records[i].of].inflections[this.records[i].for].alternates[this.records[i].alternate].script = this.records[i].script[0];
                        }
                        else this.lexemes[this.records[i].of].inflections[this.records[i].for].alternates[this.records[i].alternate].script = this.records[i].script;


                        if (Array.isArray(this.records[i].pronunciation)) this.lexemes[this.records[i].of].inflections[this.records[i].for].alternates[this.records[i].alternate].pronunciation = this.records[i].pronunciation.slice()
                        else if (this.records[i].pronunciation) this.lexemes[this.records[i].of].inflections[this.records[i].for].alternates[this.records[i].alternate].pronunciation = [this.records[i].pronunciation];
                        else this.lexemes[this.records[i].of].inflections[this.records[i].for].alternates[this.records[i].alternate].pronunciation = this.lexemes[this.records[i].of].inflections[this.records[i].for].pronunciation;

                    }

                }

                else console.log("LREC Error: Record not recognized.");

            }

            this.loaded = true;
            document.dispatchEvent(load_event);

        }

    });

    return LREC;

})();
