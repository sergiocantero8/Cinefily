<?php

namespace App\Twig;

use phpDocumentor\Reflection\Types\Mixed_;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function count;

/**
 * Class TwigHelper
 *
 * @package App\Twig
 */
class TwigHelper extends AbstractExtension
{

    /**
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        return array(
            new TwigFunction('hasSubString', array($this, 'hasSubString')),
            new TwigFunction('ucfirst', array($this, 'ucfirstString')),
            new TwigFunction('lengthArray', array($this, 'lengthArray')),
            new TwigFunction('strPosString', array($this, 'strPosString'))
        );
    }

    /**
     * @inheritDoc
     */
    public function getFilters(): array
    {
        return array(
            new TwigFilter('unSerialize', array($this, 'unSerializeString')),
            new TwigFilter('unescape', array($this, 'unescape')),
            new TwigFilter('int', static function ($value) {
                return (int)$value;
            }),
            new TwigFilter('float', static function ($value) {
                return (float)$value;
            }),
            new TwigFilter('string', static function ($value) {
                return (string)$value;
            }),
            new TwigFilter('bool', static function ($value) {
                return (bool)$value;
            }),
            new TwigFilter('array', static function ($value) {
                return (array)$value;
            }),
            new TwigFilter('object', static function ($value) {
                return (object)$value;
            }),
        );
    }


    /**
     * @param string $str
     *
     * @return string
     */
    public function ucfirstString(string $str): string
    {
        return ucfirst($str);
    }


    /**
     * @param string $str
     * @param string $needle
     * @return bool
     */
    public function strPosString(string $str, string $needle): bool
    {
        return strpos($str, $needle);
    }

    /**
     * @param array $arr
     *
     * @return int
     */
    public function lengthArray(array $arr): int
    {
        return count($arr);
    }


    /**
     * @param string $value
     *
     * @return string
     */
    public function unescape(string $value): string
    {
        return html_entity_decode($value);
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public function hasSubString(string $haystack, string $needle): bool
    {
        $find = FALSE;

        if (stripos($haystack, $needle) !== FALSE):
            $find = TRUE;
        endif;

        return $find;
    }


    #---------------------------------------------- ENTITY METHODS  --------------------------------------------------- #



}
