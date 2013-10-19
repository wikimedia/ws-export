<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* create an an odt file. Use xhtml2odt lib
* Some parts of this file are took from xhtml2odt, xhtml2odt.php, http://xhtml2odt.org/, copyright Aurélien Bompard, licence LGPLv2+
* @see http://xhtml2odt.org/
* @todo the odt style doesn't work
*/
class OdtGenerator implements FormatGenerator {
        protected $xmlContent = null;
        protected $xmlStyles = null;

        /**
        * return the extension of the generated file
        * @return string
        */
        public function getExtension() {
                return 'odt';
        }

        /**
        * return the mimetype of the generated file
        * @return string
        */
        public function getMimeType() {
                return 'application/vnd.oasis.opendocument.text';
        }

        /**
        * create the file
        * @var $data Book the title of the main page of the book in Wikisource
        * @return string
        */
        public function create(Book $book) {
                $this->setXmlContent($book);
                $this->setXmlStyles();
                $this->addStyles();
                $zip = new ZipCreator();
                $zip->addContentFile('mimetype', 'application/vnd.oasis.opendocument.text');
                $zip->addContentFile('META-INF/manifest.xml', $this->getXmlManifest($book));
                $zip->addContentFile('meta.xml', $this->getXmlMeta($book));
                $zip->addContentFile('content.xml', $this->xmlContent);
                $zip->addContentFile('style.xml', $this->xmlStyles);
                //$zip->addContentFile('settings.xml', $this->getXmlSettings());
                /*
                foreach($book->pictures as $picture) {
                        $zip->addContentFile('OPS/' . $picture->title, $picture->content);
                } */
                return $zip->getContent();
        }

        protected function setXmlContent(Book $book) {
                include(dirname(__FILE__) . '/XhtmlGenerator.php');
                $xhtmlGenerator = new XhtmlGenerator();
                $xmlContent = $xhtmlGenerator->create($book);
                $xmldoc = new DOMDocument();
                $xmldoc->loadXML($xmlContent);
                $this->xmlContent = $this->getXmlContent($this->xhtml2odt($xmldoc));
        }

        protected function setXmlStyles() {
                $this->xmlStyles = $this->getXmlStyle();
        }

        protected function getXmlManifest() {
                $content = '<?xml version="1.0" encoding="UTF-8"?>
                        <manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
                                <manifest:file-entry manifest:media-type="application/vnd.oasis.opendocument.text" manifest:full-path="/" />
                                <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="content.xml" />
                                <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="style.xml" />
                                <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="meta.xml" />
                        </manifest:manifest>';
//                                <manifest:file-entry manifest:media-type="" manifest:full-path="Thumbnails/thumbnail.png" />
                                //<manifest:file-entry manifest:media-type="" manifest:full-path="Thumbnails/" />
                return $content;
        }

        protected function getXmlMeta(Book $book) {
                $content = '<?xml version="1.0" encoding="UTF-8" ?>
                        <office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" xmlns:ooo="http://openoffice.org/2004/office" office:version="1.2">
                                <office:meta>
                                        <meta:creation-date>' . date(DATE_ISO8601) . '</meta:creation-date>
                                        <dc:language xsi:type="dcterms:RFC4646">' . $book->lang . '</dc:language>
                                        <dc:title>' . $book->name . '</dc:title>
                                        <dc:publisher>Wikisource</dc:publisher>
                                        <dc:source>' . wikisourceUrl($book->lang, $book->title) . '</dc:source>
                                        <dc:rights>http://creativecommons.org/licenses/by-sa/3.0/</dc:rights>
                                        <dc:rights>http://www.gnu.org/copyleft/fdl.html</dc:rights>
                                        <dc:creator>' . $book->author . '</dc:creator>
                                </office:meta>
                        </office:document-meta>';
                return $content;
        }

        protected function getXmlContent($content) {
                $content = '<?xml version="1.0" encoding="UTF-8"?>
                        <office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" xmlns:math="http://www.w3.org/1998/Math/MathML" xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" xmlns:ooo="http://openoffice.org/2004/office" xmlns:ooow="http://openoffice.org/2004/writer" xmlns:oooc="http://openoffice.org/2004/calc" xmlns:dom="http://www.w3.org/2001/xml-events" xmlns:xforms="http://www.w3.org/2002/xforms" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:rpt="http://openoffice.org/2005/report" xmlns:of="urn:oasis:names:tc:opendocument:xmlns:of:1.2" xmlns:rdfa="http://docs.oasis-open.org/opendocument/meta/rdfa#" xmlns:field="urn:openoffice:names:experimental:ooo-ms-interop:xmlns:field:1.0" office:version="1.2">
                                <office:scripts/>
                                <office:font-face-decls>
                                        <style:font-face style:name="Times New Roman" svg:font-family="&apos;Times New Roman&apos;" style:font-family-generic="roman" style:font-pitch="variable" />
                                        <style:font-face style:name="Arial" svg:font-family="Arial" style:font-family-generic="swiss" style:font-pitch="variable" />
                                        <style:font-face style:name="DejaVu Sans" svg:font-family="&apos;DejaVu Sans&apos;" style:font-family-generic="system" style:font-pitch="variable"/>
                                </office:font-face-decls>
                                <office:automatic-styles>
                                        <style:style style:name="T1" style:family="text">
                                                <style:text-properties fo:language="zxx" fo:country="none"/>
                                        </style:style>
                                        <style:style style:name="T2" style:family="text">
                                                <style:text-properties style:language-asian="zxx" style:country-asian="none"/>
                                        </style:style>
                                        <style:style style:name="T3" style:family="text">
                                                <style:text-properties style:language-complex="zxx" style:country-complex="none"/>
                                        </style:style>
                                </office:automatic-styles>
                                <office:body>
                                        <office:text>' . str_replace('<'.'?xml version="1.0" encoding="utf-8"?'.'>', '', $content) . '</office:text>
                                </office:body>
                        </office:document-content>';
                return $content;
        }

        protected function getXmlStyle() {
                $content = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" xmlns:math="http://www.w3.org/1998/Math/MathML" xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" xmlns:ooo="http://openoffice.org/2004/office" xmlns:ooow="http://openoffice.org/2004/writer" xmlns:oooc="http://openoffice.org/2004/calc" xmlns:dom="http://www.w3.org/2001/xml-events" xmlns:rpt="http://openoffice.org/2005/report" xmlns:of="urn:oasis:names:tc:opendocument:xmlns:of:1.2" xmlns:rdfa="http://docs.oasis-open.org/opendocument/meta/rdfa#" office:version="1.1"><office:font-face-decls><style:font-face style:name="Times New Roman" svg:font-family="&apos;Times New Roman&apos;" style:font-family-generic="roman" style:font-pitch="variable"/><style:font-face style:name="Arial" svg:font-family="Arial" style:font-family-generic="swiss" style:font-pitch="variable"/><style:font-face style:name="DejaVu Sans" svg:font-family="&apos;DejaVu Sans&apos;" style:font-family-generic="system" style:font-pitch="variable"/></office:font-face-decls><office:styles><style:default-style style:family="graphic"><style:graphic-properties draw:shadow-offset-x="0.3cm" draw:shadow-offset-y="0.3cm" draw:start-line-spacing-horizontal="0.283cm" draw:start-line-spacing-vertical="0.283cm" draw:end-line-spacing-horizontal="0.283cm" draw:end-line-spacing-vertical="0.283cm" style:flow-with-text="false"/><style:paragraph-properties style:text-autospace="ideograph-alpha" style:line-break="strict" style:writing-mode="lr-tb" style:font-independent-line-spacing="false"><style:tab-stops/></style:paragraph-properties><style:text-properties style:use-window-font-color="true" fo:font-size="12pt" fo:language="zxx" fo:country="none" style:letter-kerning="true" style:font-size-asian="12pt" style:language-asian="zh" style:country-asian="CN" style:font-size-complex="12pt" style:language-complex="hi" style:country-complex="IN"/></style:default-style><style:default-style style:family="paragraph"><style:paragraph-properties fo:hyphenation-ladder-count="no-limit" style:text-autospace="ideograph-alpha" style:punctuation-wrap="hanging" style:line-break="strict" style:tab-stop-distance="1.251cm" style:writing-mode="page"/><style:text-properties style:use-window-font-color="true" style:font-name="Times New Roman" fo:font-size="12pt" fo:language="zxx" fo:country="none" style:letter-kerning="true" style:font-name-asian="DejaVu Sans" style:font-size-asian="12pt" style:language-asian="zh" style:country-asian="CN" style:font-name-complex="DejaVu Sans" style:font-size-complex="12pt" style:language-complex="hi" style:country-complex="IN" fo:hyphenate="false" fo:hyphenation-remain-char-count="2" fo:hyphenation-push-char-count="2"/></style:default-style><style:default-style style:family="table"><style:table-properties table:border-model="collapsing"/></style:default-style><style:default-style style:family="table-row"><style:table-row-properties fo:keep-together="auto"/></style:default-style><style:style style:name="Standard" style:family="paragraph" style:class="text"/><style:style style:name="Heading" style:family="paragraph" style:parent-style-name="Standard" style:next-style-name="Text_20_body" style:class="text"><style:paragraph-properties fo:margin-top="0.423cm" fo:margin-bottom="0.212cm" fo:keep-with-next="always"/><style:text-properties style:font-name="Arial" fo:font-size="14pt" style:font-name-asian="DejaVu Sans" style:font-size-asian="14pt" style:font-name-complex="DejaVu Sans" style:font-size-complex="14pt"/></style:style><style:style style:name="Text_20_body" style:display-name="Text body" style:family="paragraph" style:parent-style-name="Standard" style:class="text"><style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0.212cm"/></style:style><style:style style:name="List" style:family="paragraph" style:parent-style-name="Text_20_body" style:class="list"/><style:style style:name="Caption" style:family="paragraph" style:parent-style-name="Standard" style:class="extra"><style:paragraph-properties fo:margin-top="0.212cm" fo:margin-bottom="0.212cm" text:number-lines="false" text:line-number="0"/><style:text-properties fo:font-size="12pt" fo:font-style="italic" style:font-size-asian="12pt" style:font-style-asian="italic" style:font-size-complex="12pt" style:font-style-complex="italic"/></style:style><style:style style:name="Index" style:family="paragraph" style:parent-style-name="Standard" style:class="index"><style:paragraph-properties text:number-lines="false" text:line-number="0"/></style:style><text:outline-style><text:outline-level-style text:level="1" style:num-format=""><style:list-level-properties text:min-label-distance="0.381cm"/></text:outline-level-style><text:outline-level-style text:level="2" style:num-format=""><style:list-level-properties text:min-label-distance="0.381cm"/></text:outline-level-style><text:outline-level-style text:level="3" style:num-format=""><style:list-level-properties text:min-label-distance="0.381cm"/></text:outline-level-style><text:outline-level-style text:level="4" style:num-format=""><style:list-level-properties text:min-label-distance="0.381cm"/></text:outline-level-style><text:outline-level-style text:level="5" style:num-format=""><style:list-level-properties text:min-label-distance="0.381cm"/></text:outline-level-style><text:outline-level-style text:level="6" style:num-format=""><style:list-level-properties text:min-label-distance="0.381cm"/></text:outline-level-style><text:outline-level-style text:level="7" style:num-format=""><style:list-level-properties text:min-label-distance="0.381cm"/></text:outline-level-style><text:outline-level-style text:level="8" style:num-format=""><style:list-level-properties text:min-label-distance="0.381cm"/></text:outline-level-style><text:outline-level-style text:level="9" style:num-format=""><style:list-level-properties text:min-label-distance="0.381cm"/></text:outline-level-style><text:outline-level-style text:level="10" style:num-format=""><style:list-level-properties text:min-label-distance="0.381cm"/></text:outline-level-style></text:outline-style><text:notes-configuration text:note-class="footnote" style:num-format="1" text:start-value="0" text:footnotes-position="page" text:start-numbering-at="document"/><text:notes-configuration text:note-class="endnote" style:num-format="i" text:start-value="0"/><text:linenumbering-configuration text:number-lines="false" text:offset="0.499cm" style:num-format="1" text:number-position="left" text:increment="5"/></office:styles><office:automatic-styles><style:page-layout style:name="Mpm1"><style:page-layout-properties fo:page-width="21.001cm" fo:page-height="29.7cm" style:num-format="1" style:print-orientation="portrait" fo:margin-top="2cm" fo:margin-bottom="2cm" fo:margin-left="2cm" fo:margin-right="2cm" style:writing-mode="lr-tb" style:footnote-max-height="0cm"><style:footnote-sep style:width="0.018cm" style:distance-before-sep="0.101cm" style:distance-after-sep="0.101cm" style:adjustment="left" style:rel-width="25%" style:color="#000000"/></style:page-layout-properties><style:header-style/><style:footer-style/></style:page-layout></office:automatic-styles><office:master-styles><style:master-page style:name="Standard" style:page-layout-name="Mpm1"/></office:master-styles></office:document-styles>';
                return $content;
        }

        protected function xhtml2odt(DOMDocument $xmldoc) {
                //$xhtml = self::cleanupInput($xhtml);
                //$xhtml = $this->handleImages($xhtml);
                $xsl = dirname(__FILE__) . '/xhtml2odt/xsl';
                $xsldoc = new DOMDocument();
                $xsldoc->load($xsl."/xhtml2odt.xsl");
                $proc = new XSLTProcessor();
                $proc->importStylesheet($xsldoc);
                return $proc->transformToXML($xmldoc);
        }

        protected function addStyles() {
                $xsl = dirname(__FILE__) . '/xhtml2odt/xsl';
                $xmlContent = new DOMDocument();
                $xmlContent->loadXML($this->xmlContent);
                $xmlStyles = new DOMDocument();
                $xmlStyles->loadXML($this->xmlStyles);
                $xsldoc = new DOMDocument();
                $xsldoc->load($xsl."/styles.xsl");
                $proc = new XSLTProcessor();
                $proc->importStylesheet($xsldoc);
                $this->xmlContent = $proc->transformToXML($xmlContent);
                $this->xmlStyles = $proc->transformToXML($xmlStyles);
        }
}

