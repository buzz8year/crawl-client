<?php

namespace crawler\models\parser;

/**
 * ParserSourceInterface defines mixed functionality, dealing with the specifics of source being parsed.
 *
 * ---
 *
 * Основным смыслом классов частных парсеров (напр. AvitoParser) является работа с данными (извлечением данных из объектов),
 * полученных от базового класса (Parser), который он расширяет и реализовывает данный интерфейс, а также
 * хранением необходимых констант, которые хоть и могут быть реализованы посредством и заменены данными
 * из соотв. "модели" (часть которых уже реализована подобным образои, а части предстоит), - не упраздняются,
 * т.к. способствуют более комфортной разработке частных парсеров, когда все перед глазами.
 *
 * На практике, сверх описанных, от случая к случаю, появляются лишь 1-2 метода для расширения метода parseCategories()
 * (в случаях когда целеообразна рекурсия и ее удобней реализовать с помощью дополнительного(-ых) метода(-ов)).
 * Подобное (и вне зависимости от цели), конечно, можно применить и к остальным методам, но пока к этому нет предпосылок.
 *
 */
interface ParserSourceInterface
{
    /**
     * Выстраивает ссылку по которой будет просходить парсинг, на основе трех строковых переменных.
     * Переменных с `ID` являются строковыми для возможности передачи пустых значений в GET запросах
     * при их выборе в веб-морде.
     *
     * Также здесь, при необходимости, можно определять статус аттрибута $pager, для указания применять ли
     * "хождение" по номерам страниц (напр. ..?page=2) при парсинге каталога, т.к. не везде это нужно (бывает
     * результаты отдаются всем скопом на одну страницу, а если об этом явно не сообщить, то цикл продожится
     * в бесконечность).
     *
     * Возвращаемый результат иcпользуется в ParserController actionTrial()
     *
     * ---
     *
     * Builds the cURL request url string, based on the parameters recieved and specific for the current Source.
     * @param integer $categorySourceId ID of requested category.
     * @param string $keyword requested keyword.
     * @param string $inputValue current url input non-empty value.
     * @return string
     */
    // public function urlBuild(string $regionSourceId, string $categorySourceId, string $keyword);
    public function urlBuild(string $regionId, string $categorySourceId, string $keyword);

    /**
     * Определяет правильную "строку" запроса на страницу (напр. ..?page=2) для данного ресурса.
     * Возвращаемый результат иcпользуется в ParserController actionTrial()
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
     * Реализовывает парсинг дерева категорий.
     * При необходимости, расширяется доп. методами, которые здесь, ест-но, не описываются.
     * Возвращаемый результат иcпользуется в классе ParserController, метод actionTree()
     *
     * ---
     *
     * Parse and structurizes category tree data of the source.
     * @return array
     */
    public function parseCategories();

    /**
     * Извлекает и структурирует  данные из объекта-результата парсинга сообщений в веб-морде ресурса, суть которых - выдача не соответствует
     * запрашиваемой и показаны результаты из других разделов (напр., так происходит на ozon.ru). Т.к. результаты парсинга
     * будут писаться под категорию, по которой мы делали запрос (а ресурс сообщает, что там нет искомых позиций),
     * то, очевидно, такие результаты нам не нужны.
     *
     * Возвращаемый результат иcпользуется в классе Parser, метод parse()
     *
     * ---
     *
     * Extracts data from parsed & found elements of any warning messages present on the pages of cateogry/search/etc.
     * @param DOMNodeList object $nodes parsed elements of warning messages.
     * @return array
     */
    public function getWarningData(\DOMNodeList $nodes);

    /**
     * Извлекает и структурирует данные (для передачи на запись в БД) из объекта-результата парсинга товаров на страницах каталога/поиска.
     *
     * Возвращаемый результат иcпользуется в классе Parser, метод parse()
     *
     * ---
     *
     * Extracts data from parsed & found elements of product items on the pages of category/search/etc.
     * @return array
     */
    public function getProducts($nodes);

    /**
     * Извлекает и структурирует данные (для передачи в методы описанные ниже) из объекта-результата парсинга объекта со всей
     * возможной информацией по товару, если такой объеут существует (если есть, обычно лежит внутри js-скрипта). Далее, если
     * такой объект искался и нашелся, то все методы описанные ниже будут извлекать соотв. инф. уже из этого объекта, а не из
     * объекта DOMNodeList полученным от метода getNodes() классa Parser.
     *
     * Возвращаемый результат иcпользуется в классе Parser, метод parse()
     *
     * ---
     *
     * Extracts data & makes Object from parsed & found element of JS script containing object with all product data possible (on product page).
     */
    public function getSuperData(\DOMNodeList $nodes);

    /**
     * Извлекает и структурирует данные (для присванивания товару, по которому был парсинг, и последующей передачи на запись в БД)
     * из объекта-результата парсинга описаний на странице товара.
     *
     * Возвращаемый результат иcпользуется в классе Parser, метод parse()
     *
     * ---
     *
     * Extracts description data from Object of getSuperData().
     * @return array
     */
    public function getDescriptionData($object);

    /**
     * Извлекает и структурирует данные (для присванивания товару, по которому был парсинг, и последующей передачи на запись в БД)
     * из объекта-результата парсинга аттрибутов на странице товара.
     *
     * Возвращаемый результат иcпользуется в классе Parser, метод parse()
     *
     * ---
     *
     * Extracts attribute data from Object of getSuperData().
     * @return array
     */
    public function getAttributeData($object);

    /**
     * Извлекает и структурирует данные (для присванивания товару, по которому был парсинг, и последующей передачи на запись в БД)
     * из объекта-результата парсинга изображений на странице товара.
     *
     * Возвращаемый результат иcпользуется в классе Parser, метод parse()
     *
     * ---
     *
     * Extracts image data from Object of getSuperData().
     * @return array
     */
    public function getImageData($object);

}
