<?php

/**
 * PHP version 7.1
 *
 * @package CodeReview\Crypto100
 */

namespace CodeReview\CryptoTop;

use \CodeReview\HTTP\HTTPclient;
use \DOMDocument;
use \DOMXPath;
use \CodeReview\XMLGenerator;

/**
 * Crypto rating
 */
final class CryptoRating implements ValidatorInterface, RatingInterface
{
    /**
     * Mining algorithms
     *
     * @var array
     */
    protected $algorithms;

    /**
     * Prepare class to work
     *
     * @return void
     */

    public function __construct()
    {
        $algorithms = new Algorithms();

        $this->algorithms = $algorithms->get();
    } //end __construct()


    /**
     * Get cryptocurrency rating
     *
     * @return array Rating
     */
    public function get(): array
    {
        $httpclient = new HTTPclient(COINMARKETCAP_URL);
        $html       = $httpclient->get();
        $parsed     = $this->_parseHTML($html);

        return $parsed;
    } //end get()


    /**
     * Parse HTML
     *
     * @param string $html Html to parse
     *
     * @return array Data
     */
    private function _parseHTML(string $html): array
    {
        $parsed = [];

        $dom = new DOMDocument("1.0", "utf-8");
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $list  = $xpath->query("//div[@class='table-fixed-column-mobile compact-name-column']/table/tbody/tr");

        foreach ($list as $element) {
            $tr   = $dom->saveHTML($element);
            $data = $this->readTr($tr);

            $data["image"]     = $this->_downloadImage($data["image"]);
            $data["marketcap"] = number_format($data["marketcap"], 0, '', '');

            $parsed[] = $data;
        }

        return $parsed;
    } //end _parseHTML()


    /**
     * Download image
     *
     * @param string $url Image url
     *
     * @return string Base64 encoded image
     */
    private function _downloadImage(string $image): string
    {
        $http  = new HTTPclient(str_replace("https://files.coinmarketcap.com", FILES_COINMARKETCAP_URL, $image));
        $image = $http->get();

        return base64_encode($image);
    } //end _downloadImage()


    /**
     * Read tr element
     *
     * @param string $tr Html code of element
     *
     * @return array Tr element data
     */
    protected function readTr(string $tr): array
    {
        $parsed = $this->parseHTML($tr);

        return $parsed;
    } //end readTr()


    /**
     * Parse table element HTML
     *
     * @param string $html Tr html data
     *
     * @return array Parsed data
     */
    protected function parseHTML(string $html): array
    {
        $parsed = [];

        $dom = new DOMDocument("1.0", "utf-8");
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $fields = [
            "ticker"             => "//td[@class='no-wrap currency-name']/span",
            "name"               => "//td[@class='no-wrap currency-name']/a",
            "marketcap"          => "//td[@class='no-wrap market-cap text-right']",
            "price"              => "//td[@class='no-wrap text-right']/a[@class='price']",
            "volume"             => "//td[@class='no-wrap text-right']/a[@class='volume']",
            "circulating_supply" => "//td[@class='no-wrap text-right circulating-supply']/a/span[1] | //td[@class='no-wrap text-right circulating-supply']/span/span[1]",
            "change"             => "//td[(last() - 1)]",
            "image"              => "//td[last()]/a/img/@src",
        ];

        foreach ($fields as $fieldname => $path) {
            $list = $xpath->query($path);
            if ($list->length > 0) {
                $parsed[$fieldname] = preg_replace("/(\n+|\s+|[$]+|,)/ui", "", $list[0]->textContent);
            } //end if

        } //end foreach

        $this->_addCoinInfo($xpath, $parsed);
        $parsed["minable"] = $this->_miningCheck($xpath);

        if (isset($this->algorithms[$parsed["ticker"]]) === true) {
            $algodata = $this->algorithms[$parsed["ticker"]];

            $parsed["algorithm"] = $algodata["algorithm"];
            $parsed["device"]    = $algodata["device"];
        } //end if

        return $parsed;
    } //end parseHTML()


    /**
     * Check currency mining
     *
     * @param DOMXpath $xpath Dom xpath of table element
     *
     * @return bool True or false, minable status
     */
    private function _miningCheck(DOMXPath $xpath): bool
    {
        $list = $xpath->query("//td[@class='no-wrap text-right circulating-supply']");

        return ((preg_match("/\*/ui", $list[0]->textContent) > 0) ? false : true);
    } //end _miningCheck()


    /**
     * Add coin information to parsed data array
     *
     * @param DOMXPath $xpath  Dom xpath of table element
     * @param array    $parsed Parsed data
     *
     * @return void
     */
    private function _addCoinInfo(DOMXPath $xpath, array &$parsed)
    {
        $list       = $xpath->query("//td[@class='no-wrap currency-name']/a/@href");
        $httpclient = new HTTPclient(COINMARKETCAP_URL . $list[0]->textContent);

        $html = $httpclient->get();
        $dom  = new DOMDocument("1.0", "utf-8");
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $list  = $xpath->query("//div/h1[@class='text-large']/img/@src");
        $httpclient->setRequest(str_replace("https://files.coinmarketcap.com", FILES_COINMARKETCAP_URL,
            $list[0]->textContent));

        $list            = $xpath->query("//div[@class='col-sm-4 col-sm-pull-8']/ul[@class='list-unstyled']/li/a[text()[contains(.,'Website')]]");
        $parsed["sites"] = [];

        foreach ($list as $element) {
            $parsed["sites"][] = $element->getAttribute("href");
        } //end foreach

        $coin  = $xpath->query("//div[@class='col-sm-4 col-sm-pull-8']/ul[@class='list-unstyled']/li/small/span[text()[contains(.,'Coin')]]");
        $token = $xpath->query("//div[@class='col-sm-4 col-sm-pull-8']/ul[@class='list-unstyled']/li/small/span[text()[contains(.,'Token')]]");
        if ($coin->length === 1) {
            $parsed["type"] = "coin";
        } else {
            if ($token->length === 1) {
                $parsed["type"] = "token";
            }
        } //end if

        $parsed["logo"] = base64_encode($httpclient->get());
    } //end _addCoinInfo()


    /**
     * Validate result
     *
     * @param array $result Result of grabbing top 100 cryptocurrencies
     *
     * @return bool Validate result
     */

    public function validate(array $result): bool
    {
        // libxml_use_internal_errors(true);
        $xml = new XMLGenerator("top");
        foreach ($result as $currency) {
            $element = $xml->newElement("currency", "", [], null, true);
            $sites   = $currency["sites"];
            unset($currency["sites"]);

            foreach ($currency as $name => $value) {
                $xml->newElement(preg_replace("/\s+/ui", "", $name), $value, [], $element);
            } //end foreach

            $siteselement = $xml->newElement("sites", "", [], $element, true);
            foreach ($sites as $site) {
                $xml->newElement("site", $site, [], $siteselement);
            } //end foreach

        } //end foreach

        return $xml->getDoc()->schemaValidate(__DIR__ . "/schemas/top.xsd");
    } //end validate()


} //end class

