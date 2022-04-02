<?php

/**
 * PHP から HTML を操作する
 * ref: https://qiita.com/economist/items/aefccb2f073ed9429607
 */
class Document extends \DOMDocument { // https://www.php.net/manual/ja/class.domdocument.php

    function __construct($str = '<!DOCTYPE html><html lang="ja"><head><meta charset="utf-8"><title></title></head><body></body></html>'){
        parent::__construct();
        $this->registerNodeClass('\DOMElement','HTMLElement');
        $this->registerNodeClass('\DOMDocumentFragment','HTMLFragment');
        libxml_use_internal_errors(true);

        $pos = strpos($str, '<');

        if($pos >= 0 and $str[$pos+1] === '!'){
            $this->contentsType = 'html';
            $this->loadHTML(substr($str, $pos), LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NONET | LIBXML_COMPACT);
        }
        else if($pos >= 0 and $str[$pos+1] === '?'){
            $this->contentsType = 'xml';
            $this->loadXML(substr($str, $pos), LIBXML_NONET | LIBXML_COMPACT); // https://www.php.net/manual/ja/libxml.constants.php
        }
        else{
            $this->contentsType = 'fragment';
            $this->loadHTML('<?xml encoding="utf-8">'.$str, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NONET | LIBXML_COMPACT);
        }
    }


    function __get($name){
        if(in_array($name, ['html','head','body','title'], true)){
            return $this->getElementsByTagName($name)[0];
        }
        else{
            return $this->getElementById($name);
        }
    }


    function __invoke($selector, $text = null, $attr = []){
        if($selector instanceof self){
            return $this->importNode($selector->documentElement, true);
        }
        else if($selector instanceof \DOMNode){
            return $this->importNode($selector, true);
        }
        else if(preg_match('/</', $selector)){
            if(preg_match('/^<([\w\-]+)>$/', $selector, $m)){
                return $this->createHTMLElement($m[1], $text, $attr);
            }
            else{
                return self::createHTMLFragment($this, $selector);
            }
        }
        else if($selector[0] === '*'){
            if(strlen($selector) > 1){
                $selector = substr($selector, 1);
            }
            return self::searchElement($selector, $text, $this, true);
        }
        else{
            return self::searchElement($selector, $text, $this);
        }
    }


    function __toString(){
        $this->formatOutput = true;

        if($this->contentsType === 'html'){
            return $this->saveXML($this->doctype) . "\n" . $this->saveHTML($this->documentElement);
        }
        else if($this->contentsType === 'xml'){
            return $this->saveXML($this->doctype) . "\n" . $this->saveXML($this->documentElement);
        }
        else{
            return $this->saveHTML($this->documentElement);
        }
    }


    function querySelector($selector, $context = null){
        return self::searchElement($selector, $context, $this);
    }


    function querySelectorAll($selector, $context = null){
        return self::searchElement($selector, $context, $this, true);
    }


    private function createHTMLElement($tagName, $text = '', $attr = []){
        $el = $this->createElement($tagName);
        foreach($attr as $k => $v){
            $el->setAttribute($k, $v);
        }

        if(is_array($text)){
            if($tagName === 'table'){
                $el = $this->createTableElement($el, $text);
            }
            else if($tagName === 'select'){
                $el = $this->createSelectElement($el, $text);
            }
            else if($tagName === 'ol' or $tagName === 'ul'){
                $el = $this->createListElement($el, $text);
            }
        }
        else{
            $el->textContent = $text;
        }

        return $el;
    }


    private function createListElement($el, array $contents){
        foreach($contents as $v){
            $child = $this->createElement('li', $v);
            $el->appendChild($child);
        }
        return $el;
    }


    private function createSelectElement($el, array $contents){
        foreach($contents as $v){
            $child = $this->createElement('option', $v);
            $child->setAttribute('value', $v);
            $el->appendChild($child);
        }
        return $el;
    }


    private function createTableElement($el, array $contents){
        foreach($contents as $row){
            $tr = $this->createElement('tr');
            $el->appendChild($tr);
            foreach((array)$row as $cell){
                $td = $this->createElement('td', $cell);
                $tr->appendChild($td);
            }
        }
        return $el;
    }


    static function createHTMLFragment($document, $str){
        $fragment = $document->createDocumentFragment();
        //$fragment->appendXML($str);
        $dummy    = new self("<dummy>$str</dummy>");
        foreach($dummy->documentElement->childNodes as $child){
            $fragment->appendChild($document->importNode($child, true));
        }
        return $fragment;
    }


    static function searchElement($selector, $context, $document, $all = false){
        $selector = self::selector2xpath($selector, $context);
        $result   = (new \DOMXPath($document))->query($selector, $context);
        return $all ? iterator_to_array($result) : $result[0];
    }


    static function selector2xpath($input_selector, $context = null){
        $selector = trim($input_selector);
        $last     = '';
        $element  = true;
        $parts[]  = $context ? './/' : '//';
        $regex    = [
            'element'    => '/^(\*|[a-z_][a-z0-9_-]*|(?=[#.\[]))/i',
            'id_class'   => '/^([#.])([a-z0-9*_-]*)/i',
            'attribute'  => '/^\[\s*([^~|=\s]+)\s*([~|]?=)\s*"([^"]+)"\s*\]/',
            'attr_box'   => '/^\[([^\]]*)\]/',
            'combinator' => '/^(\s*[>+~\s,])/i',
        ];

        $pregMatchDelete = function ($pattern, &$subject, &$matches){ // 正規表現でマッチをしつつ、マッチ部分を削除
            if (preg_match($pattern, $subject, $matches)) {
                $subject = substr($subject, strlen($matches[0]));
                return true;
            }
        };

        while (strlen(trim($selector)) && ($last !== $selector)){
            $selector = $last = trim($selector);

            // Elementを取得
            if($element){
                if ($pregMatchDelete($regex['element'], $selector, $e)){
                    $parts[] = ($e[1] === '') ? '*' : $e[1];
                }
                $element = false;
            }

            // IDとClassの指定を取得
            if($pregMatchDelete($regex['id_class'], $selector, $e)) {
                switch ($e[1]){
                    case '.':
                        $parts[] = '[contains(concat( " ", @class, " "), " ' . $e[2] . ' ")]';
                        break;
                    case '#':
                        $parts[] = '[@id="' . $e[2] . '"]';
                        break;
                }
            }

            // atribauteを取得
            if($pregMatchDelete($regex['attribute'], $selector, $e)) {
                switch ($e[2]){ // 二項(比較)
                    case '!=':
                        $parts[] = '[@' . $e[1] . '!=' . $e[3] . ']';
                        break;
                    case '~=':
                        $parts[] = '[contains(concat( " ", @' . $e[1] . ', " "), " ' . $e[3] . ' ")]';
                        break;
                    case '|=':
                        $parts[] = '[@' . $e[1] . '="' . $e[3] . '" or starts-with(@' . $e[1] . ', concat( "' . $e[3] . '", "-"))]';
                        break;
                    default:
                        $parts[] = '[@' . $e[1] . '="' . $e[3] . '"]';
                        break;
                }
            }
            else if ($pregMatchDelete($regex['attr_box'], $selector, $e)) {
                $parts[] = '[@' . $e[1] . ']';  // 単項(存在性)
            }

             // combinatorとカンマがあったら、区切りを追加。また、次は型選択子又は汎用選択子でなければならない
            if ($pregMatchDelete($regex['combinator'], $selector, $e)) {
                switch (trim($e[1])) {
                    case ',':
                        $parts[] = ' | //*';
                        break;
                    case '>':
                        $parts[] = '/';
                        break;
                    case '+':
                        $parts[] = '/following-sibling::*[1]/self::';
                        break;
                    case '~': // CSS3
                        $parts[] = '/following-sibling::';
                        break;
                    default:
                        $parts[] = '//';
                        break;
                }
                $element = true;
            }
        }
        return implode('', $parts);
    }
}



class HTMLElement extends \DOMElement{ // https://www.php.net/manual/ja/class.domelement.php

    function __construct() {
        parent::__construct();
    }


    function __get($name){
        if($name === 'innerHTML'){
            $result = '';
            foreach($this->childNodes as $child){
                $result .= $this->ownerDocument->saveHTML($child);
            }
            return $result;
        }
        else if($name === 'outerHTML'){
            return $this->ownerDocument->saveHTML($this);
        }
        else if($name === 'children'){
            $children = [];
            foreach($this->childNodes as $v){
                if($v->nodeType === XML_ELEMENT_NODE){
                    $children[] = $v;
                }
            }
            return $children;
        }
        else{
            return $this->getAttribute($name);
        }
    }


    function __set($name, $value){
        if($name === 'innerHTML'){
            $fragment = document::createHTMLFragment($this->ownerDocument, $value);
            $this->textContent = '';
            $this->appendChild($fragment);
        }
        else if($name === 'outerHTML'){
            $fragment = document::createHTMLFragment($this->ownerDocument, $value);
            $this->parentNode->replaceChild($fragment, $this);
        }
        else{
            $this->setAttribute($name, $value);
        }
    }


    function __unset($name){
        $this->removeAttribute($name);
    }


    function __isset($name){
        return $this->hasAttribute($name);
    }


    function __toString(){
        return $this->ownerDocument->saveHTML($this);
    }


    function querySelector($selector){
        return document::searchElement($selector, $this, $this->ownerDocument);
    }


    function querySelectorAll($selector){
        return document::searchElement($selector, $this, $this->ownerDocument, true);
    }
}



class HTMLFragment extends \DOMDocumentFragment{ // https://www.php.net/manual/ja/class.domdocumentfragment.php

    function __construct() {
        parent::__construct();
    }


    function __get($name){
        if($name === 'innerHTML'){
            $result = '';
            foreach($this->childNodes as $child){
                $result .= $this->ownerDocument->saveHTML($child);
            }
            return $result;
        }
        else if($name === 'outerHTML'){
            return $this->ownerDocument->saveHTML($this);
        }
        else if($name === 'children'){
            $children = [];
            foreach($this->childNodes as $v){
                if($v->nodeType === XML_ELEMENT_NODE){
                    $children[] = $v;
                }
            }
            return $children;
        }
        else{
            return document::searchElement("#$name", $this, $this->ownerDocument);
        }
    }


    function querySelector($selector){
        return document::searchElement($selector, $this, $this->ownerDocument);
    }


    function querySelectorAll($selector){
        return document::searchElement($selector, $this, $this->ownerDocument, true);
    }


    function __toString(){
        return $this->ownerDocument->saveHTML($this);
    }
}
