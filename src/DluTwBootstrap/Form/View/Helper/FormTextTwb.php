<?php
namespace DluTwBootstrap\Form\View\Helper;

use DluTwBootstrap\Form\FormUtil;
use DluTwBootstrap\GenUtil;

use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\FormText;

class FormTextTwb extends FormText
{
    /**
     * @var FormUtil
     */
    protected $formUtil;

    /**
     * @var GenUtil
     */
    protected $genUtil;

    /* ******************** METHODS ******************** */

    /**
     * Constructor
     * @param \DluTwBootstrap\GenUtil $genUtil
     * @param \DluTwBootstrap\Form\FormUtil $formUtil
     */
    public function __construct(GenUtil $genUtil, FormUtil $formUtil) {
        $this->genUtil  = $genUtil;
        $this->formUtil = $formUtil;
    }

    /**
     * Prepares the element prior to rendering
     * @param \Zend\Form\ElementInterface $element
     * @param string $formType
     * @param array $displayOptions
     * @return void
     */
    protected function prepareElementBeforeRendering(ElementInterface $element, $formType, array $displayOptions) {
        if(!$this->formUtil->isFormTypeSupported($formType)) {
            $formType   = $this->formUtil->getDefaultFormType();
        }
        if(array_key_exists('class', $displayOptions)) {
            $class  = $element->getAttribute('class');
            $class  = $this->genUtil->addWord($displayOptions['class'], $class);
            $element->setAttribute('class', $class);
        }
        if($formType == FormUtil::FORM_TYPE_SEARCH) {
            $class  = $element->getAttribute('class');
            $class  = $this->genUtil->addWord('search-query', $class);
            $element->setAttribute('class', $class);
        }
        $this->formUtil->addIdAttributeIfMissing($element);
    }

    /**
     * Render a form <input> text element from the provided $element,
     * @param  ElementInterface $element
     * @param  null|string $formType
     * @param  array $displayOptions
     * @return string
     */
    public function render(ElementInterface $element,
                           $formType = null,
                           array $displayOptions = array()
    ) {
        $this->prepareElementBeforeRendering($element, $formType, $displayOptions);
        $html   = parent::render($element);
        //Text prepend / append
        $escapeHelper   = $this->getEscapeHtmlHelper();
        $prepAppClass   = '';
        if($element->getOption('prependText')) {
            $prepAppClass   = $this->genUtil->addWord('input-prepend', $prepAppClass);
            $html           = '<span class="add-on">' . $escapeHelper($element->getOption('prependText')) . '</span>'
                . $html;
        }
        if($element->getOption('appendText')) {
            $prepAppClass   = $this->genUtil->addWord('input-append', $prepAppClass);
            $html           .= '<span class="add-on">' . $escapeHelper($element->getOption('appendText')) . '</span>';
        }
        if($prepAppClass) {
            $html           = '<div class="' . $prepAppClass . '">' . "\n$html\n" . '</div>';
        }
        return $html;
    }

    /**
     * Invoke helper as function
     * Proxies to {@link render()}.
     * @param  ElementInterface|null $element
     * @param  null|string $formType
     * @param  array $displayOptions
     * @return string|FormTextTwb
     */
    public function __invoke(ElementInterface $element = null, $formType = null, array $displayOptions = array()
    ) {
        if (!$element) {
            return $this;
        }
        return $this->render($element, $formType, $displayOptions);
    }
}