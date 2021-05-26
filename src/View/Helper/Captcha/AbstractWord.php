<?php

namespace Laminas\Form\View\Helper\Captcha;

use Laminas\Captcha\AdapterInterface as CaptchaAdapter;
use Laminas\Form\Element\Captcha;
use Laminas\Form\ElementInterface;
use Laminas\Form\Exception;
use Laminas\Form\View\Helper\FormInput;

use function array_key_exists;
use function assert;
use function in_array;
use function is_array;
use function method_exists;
use function sprintf;
use function strtolower;

abstract class AbstractWord extends FormInput
{
    public const CAPTCHA_APPEND  = 'append';
    public const CAPTCHA_PREPEND = 'prepend';

    /** @var FormInput */
    protected $inputHelper;

    /** @var string */
    protected $captchaPosition = self::CAPTCHA_APPEND;

    /**
     * Separator string for captcha and inputs
     *
     * @var string
     */
    protected $separator = '';

    /**
     * Invoke helper as functor
     *
     * Proxies to {@link render()}.
     *
     * @return string|self
     */
    public function __invoke(?ElementInterface $element = null)
    {
        if (! $element) {
            return $this;
        }

        return $this->render($element);
    }

    /**
     * Render captcha form elements for the given element
     *
     * Creates and returns:
     * - Hidden input with captcha identifier (name[id])
     * - Text input for entering captcha value (name[input])
     *
     * More specific renderers will consume this and render it.
     *
     * @throws Exception\DomainException
     * @return string
     */
    protected function renderCaptchaInputs(ElementInterface $element)
    {
        $name = $element->getName();
        if ($name === null || $name === '') {
            throw new Exception\DomainException(sprintf(
                '%s requires that the element has an assigned name; none discovered',
                __METHOD__
            ));
        }

        assert($element instanceof Captcha);
        $captcha = $element->getCaptcha();

        if ($captcha === null || ! $captcha instanceof CaptchaAdapter) {
            throw new Exception\DomainException(sprintf(
                '%s requires that the element has a "captcha" attribute implementing Laminas\Captcha\AdapterInterface; '
                . 'none found',
                __METHOD__
            ));
        }

        $attributes = $element->getAttributes();
        $hidden     = $this->renderCaptchaHidden($captcha, $attributes);
        $input      = $this->renderCaptchaInput($captcha, $attributes);

        return $hidden . $input;
    }

    /**
     * Render the hidden input with the captcha identifier
     *
     * @param  array          $attributes
     * @return string
     */
    protected function renderCaptchaHidden(CaptchaAdapter $captcha, array $attributes)
    {
        $attributes['type']  = 'hidden';
        $attributes['name'] .= '[id]';

        if (isset($attributes['id'])) {
            $attributes['id'] .= '-hidden';
        }

        if (method_exists($captcha, 'getId')) {
            $attributes['value'] = $captcha->getId();
        } elseif (array_key_exists('value', $attributes)) {
            if (is_array($attributes['value']) && array_key_exists('id', $attributes['value'])) {
                $attributes['value'] = $attributes['value']['id'];
            }
        }
        $closingBracket = $this->getInlineClosingBracket();
        return sprintf(
            '<input %s%s',
            $this->createAttributesString($attributes),
            $closingBracket
        );
    }

    /**
     * Render the input for capturing the captcha value from the client
     *
     * @param  array          $attributes
     * @return string
     */
    protected function renderCaptchaInput(CaptchaAdapter $captcha, array $attributes)
    {
        $attributes['type']  = 'text';
        $attributes['name'] .= '[input]';
        if (array_key_exists('value', $attributes)) {
            unset($attributes['value']);
        }
        $closingBracket = $this->getInlineClosingBracket();
        return sprintf(
            '<input %s%s',
            $this->createAttributesString($attributes),
            $closingBracket
        );
    }

    /**
     * Set value for captchaPosition
     *
     * @param  mixed $captchaPosition
     * @throws Exception\InvalidArgumentException
     * @return $this
     */
    public function setCaptchaPosition($captchaPosition)
    {
        $captchaPosition = strtolower($captchaPosition);
        if (! in_array($captchaPosition, [self::CAPTCHA_APPEND, self::CAPTCHA_PREPEND])) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects either %s::CAPTCHA_APPEND or %s::CAPTCHA_PREPEND; received "%s"',
                __METHOD__,
                self::class,
                self::class,
                (string) $captchaPosition
            ));
        }
        $this->captchaPosition = $captchaPosition;

        return $this;
    }

    /**
     * Get position of captcha
     *
     * @return string
     */
    public function getCaptchaPosition()
    {
        return $this->captchaPosition;
    }

    /**
     * Set separator string for captcha and inputs
     *
     * @param  string $separator
     * @return $this
     */
    public function setSeparator($separator)
    {
        $this->separator = (string) $separator;
        return $this;
    }

    /**
     * Get separator for captcha and inputs
     *
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }
}
