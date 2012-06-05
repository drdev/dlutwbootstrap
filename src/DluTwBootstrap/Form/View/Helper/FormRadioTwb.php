<?php
namespace DluTwBootstrap\Form\View\Helper;

use Zend\Form\ElementInterface;

class FormRadioTwb extends \Zend\Form\View\Helper\FormRadio
{
    /**
     * Render as inline radio?
     * @var bool
     */
    protected $inline;

    /* ************************ METHODS ***************************** */

    /**
     * Invoke helper as function
     * @param \Zend\Form\ElementInterface $element
     * @param bool $inline
     * @return string
     */
    public function __invoke(ElementInterface $element, $inline = false) {
        $this->labelHelper  = null;
        $this->inline       = (bool)$inline;
        $html               = parent::__invoke($element);
        return $html;
    }

    /**
     * Retrieve the FormLabel helper
     *
     * @return FormLabelRadioOptionTwb
     * @throws \Exception
     */
    protected function getLabelHelper() {
        if ($this->labelHelper) {
            return $this->labelHelper;
        }

        if ($this->view instanceof \Zend\Loader\Pluggable) {
            if($this->inline) {
                $this->labelHelper = $this->view->plugin('form_label_radio_option_inline_twb');
            } else {
                $this->labelHelper = $this->view->plugin('form_label_radio_option_twb');
            }
        }

        if (!$this->labelHelper instanceof AbstractFormLabel) {
            throw new \Exception('Wrong type of label helper.');
        }
        return $this->labelHelper;
    }

    //TODO - remove the render() method once the bug with swapped multi-option keys/values has been fixed in ZF2
    //TODO - do not forget to add the description! See the end of this method.
    /**
     * Render a form <input> element from the provided $element
     *
     * @param  ElementInterface $element
     * @return string
     */
    public function render(ElementInterface $element)
    {
        $name   = static::getName($element);
        if (empty($name)) {
            throw new \Zend\Form\Exception\DomainException(sprintf(
                                                    '%s requires that the element has an assigned name; none discovered',
                                                    __METHOD__
                                                ));
        }

        $attributes         = $element->getAttributes();

        if (!isset($attributes['options'])
            || (!is_array($attributes['options']) && !$attributes['options'] instanceof Traversable)
        ) {
            throw new \Zend\Form\Exception\DomainException(sprintf(
                                                    '%s requires that the element has an array or Traversable "options" attribute; none found',
                                                    __METHOD__
                                                ));
        }

        $options = $attributes['options'];
        unset($attributes['options']);

        $attributes['name'] = $name;
        $attributes['type'] = $this->getInputType();

        $values = array();
        if (isset($attributes['value'])) {
            $values = (array) $attributes['value'];
            unset($attributes['value']);
        }

        $inputHelper    = $this->getInputHelper();
        $escapeHelper   = $this->getEscapeHelper();
        $labelHelper    = $this->getLabelHelper();
        $labelOpen      = $labelHelper->openTag();
        $labelClose     = $labelHelper->closeTag();
        $labelPosition  = $this->getLabelPosition();
        $closingBracket = $this->getInlineClosingBracket();
        $template       = $labelOpen . '%s%s' . $labelClose;
        $combinedMarkup = array();
        $count          = 0;

        foreach ($options as $value => $label) {
            $count++;
            if ($count > 1 && array_key_exists('id', $attributes)) {
                unset($attributes['id']);
            }
            $attributes['value']   = $value;
            $attributes['checked'] = '';
            if (in_array($value, $values, true)) {
                $attributes['checked'] = 'checked';
            }

            $label = $escapeHelper($label);
            $input = sprintf(
                '<input %s%s',
                $this->createAttributesString($attributes),
                $closingBracket
            );

            switch ($labelPosition) {
                case self::LABEL_PREPEND:
                    $markup = sprintf($template, $label, $input);
                    break;
                case self::LABEL_APPEND:
                default:
                    $markup = sprintf($template, $input, $label);
                    break;
            }

            $combinedMarkup[] = $markup;
        }

        $html = implode($this->getSeparator(), $combinedMarkup);

        //TODO - when removing this method, refactor to add description to the element
        $renderer           = $this->getView();
        //Description
        $descriptionHelper  = $renderer->plugin('form_element_description_twb');
        $html               .= $descriptionHelper($element);

        return $html;
    }
}