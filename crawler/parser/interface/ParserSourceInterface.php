<?php

namespace crawler\parser\interface;

/**
 * ParserSourceInterface defines mixed functionality, dealing with the specifics of source being parsed.
 *
 * ---
 *
 * The primary purpose of specific parser classes (e.g., RdsParser) is to work with data (extracting data from objects)
 * obtained from the base class (Parser), which it extends and implements this interface, as well as
 * storing necessary constants, which, although they can be implemented and replaced with data
 * from the corresponding "model" (some of which are already implemented in this way, and others are planned), 
 * are not eliminated because they contribute to more convenient development of specific parsers when everything is at hand.
 *
 * In practice, beyond the described functionality, only 1-2 methods occasionally appear to extend the parseCategories() method
 * (in cases where recursion is appropriate and it is more convenient to implement it using an additional method(s)).
 * Similar approaches (regardless of the purpose) can, of course, be applied to other methods, but there are currently no prerequisites for this.
 *
 */
interface ParserSourceInterface
{
    /**
     * Builds the URL for parsing based on three string variables.
     * The variables with `ID` are strings to allow passing empty values in GET requests
     * when selected in the web interface.
     *
     * Additionally, if necessary, the status of the $pager attribute can be determined here to indicate whether
     * to use "pagination" (e.g., ..?page=2) when parsing the catalog, as it is not always needed (sometimes
     * results are provided all at once on a single page, and if this is not explicitly indicated, the loop will
     * continue indefinitely).
     *
     * The returned result is used in the ParserController actionTrial().
     *
     * ---
     *
     * Builds the cURL request url string, based on the parameters recieved and specific for the current Source.
     * @param integer $categorySourceId ID of requested category.
     * @param string $keyword requested keyword.
     * @param string $inputValue current url input non-empty value.
     * @return string
     */
    // public function buildUrl(string $regionSourceId, string $categorySourceId, string $keyword);
    public function buildUrl(string $regionId, string $categorySourceId, string $keyword);

    /**
     * Determines the correct "string" for the page request (e.g., ..?page=2) for this resource.
     * 
     * The returned result is used in the ParserController actionTrial().
     *
     * ---
     *
     * Builds url page query string (i.e. &page=2, ?page=2, etc.), based on the parameters recieved and specific for the current Source.
     * @param integer $page number of the page.
     * @param string $url current value of url, needed to determine the delimiter (&/?) , etc.
     * @return string
     */
    public function pageQuery(int $page, string $url);

    /**
     * Extracts and structures data from the object resulting from parsing messages in the web interface of the resource, 
     * the essence of which is that the output does not match the requested and shows results from other sections (e.g., 
     * this happens on ozon.ru). Since the parsing results will be written under the category for which we made the request 
     * (and the resource reports that there are no desired items there), 
     * it is obvious that such results are not needed.
     *
     * The returned result is used in the Parser class, parse() method.
     *
     * ---
     *
     * Extracts data from parsed & found elements of any warning messages present on the pages of cateogry/search/etc.
     * @param $nodes parsed elements of warning messages.
     * @return array
     */
    public function getWarningData($nodes);

    /**
     * Extracts and structures data (to be passed for saving to the database) from the object resulting from parsing products on catalog/search pages.
     *
     * The returned result is used in the Parser class, parse() method.
     *
     * ---
     *
     * Extracts data from parsed & found elements of product items on the pages of category/search/etc.
     * @return array
     */
    public function getProducts($nodes);

    /**
     * Extracts and structures data (to be passed to the methods described below) from the object resulting from parsing 
     * an object containing all possible product information, if such an object exists (if present, it is usually found 
     * inside a JS script). Further, if such an object was searched for and found, all the methods described below will 
     * extract the corresponding information from this object, rather than from the DOMNodeList object obtained from the 
     * getNodes() method of the Parser class.
     *
     * The returned result is used in the Parser class, parse() method.
     *
     * ---
     *
     * Extracts data & makes Object from parsed & found element of JS script containing object with all product data possible (on product page).
     */
    public function getSuperData($nodes);

    /**
     * Extracts and structures data (to assign to the product being parsed and subsequently save to the database)
     * from the object resulting from parsing descriptions on the product page.
     *
     * The returned result is used in the Parser class, parse() method.
     *
     * ---
     *
     * Extracts description data from Object of getSuperData().
     * @return array
     */
    public function getDescriptionData($object);

    /**
     * Extracts and structures data (to assign to the product being parsed and subsequently save to the database)
     * from the object resulting from parsing attributes on the product page.
     *
     * The returned result is used in the Parser class, parse() method.
     *
     * ---
     *
     * Extracts attribute data from Object of getSuperData().
     * @return array
     */
    public function getAttributeData($object);

    /**
     * Extracts and structures data (to assign to the product being parsed and subsequently save to the database)
     * from the object resulting from parsing images on the product page.
     *
     * The returned result is used in the Parser class, parse() method.
     *
     * ---
     *
     * Extracts image data from Object of getSuperData().
     * @return array
     */
    public function getImageData($object);

    /**
     * Implements parsing of the category tree.
     * If necessary, it can be extended with additional methods, which are naturally not described here.
     * The returned result is used in the ParserController class, actionTree() method.
     *
     * ---
     *
     * Parses and structurizes category tree data of the source.
     * @return array
     */
    public function parseCategories();
}
