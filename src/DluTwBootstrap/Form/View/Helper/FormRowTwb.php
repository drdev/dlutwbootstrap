<?php
namespace DluTwBootstrap\Form\View\Helper;

use DluTwBootstrap\Form\View\Helper\FormElementTwb;
use DluTwBootstrap\Form\View\Helper\FormHintTwb;
use DluTwBootstrap\Form\View\Helper\FormDescriptionTwb;
use DluTwBootstrap\Form\View\Helper\FormElementErrorsTwb;
use DluTwBootstrap\Form\View\Helper\FormControlGroupTwb;
use DluTwBootstrap\Form\View\Helper\FormControlsTwb;
use DluTwBootstrap\Form\Exception\UnsupportedHelperTypeException;
use DluTwBootstrap\GenUtil;
use DluTwBootstrap\Form\FormUtil;

use Zend\Form\View\Helper\FormLabel;
use Zend\Form\ElementInterface;
use Zend\Form\Exception;
use Zend\Form\View\Helper\AbstractHelper;

//TODO - refactor

class FormRowTwb extends AbstractHelper
{
    const LABEL_APPEND  = 'append';
    const LABEL_PREPEND = 'prepend';

    /**
     * @var string
     */
    protected $labelPosition = self::LABEL_PREPEND;

    /**
     * @var array
     */
    protected $labelAttributes      = array();

    /**
     * @var FormLabel
     */
    protected $labelHelper;

    /**
     * @var FormElementTwb
     */
    protected $elementHelper;

    /**
     * @var FormElementErrorsTwb
     */
    protected $elementErrorsHelper;

    /**
     * @var FormHintTwb
     */
    protected $hintHelper;

    /**
     * @var FormDescriptionTwb
     */
    protected $descriptionHelper;

    /**
     * @var FormControlGroupTwb
     */
    protected $controlGroupHelper;

    /**
     * @var FormControlsTwb
     */
    protected $controlsHelper;

    /**
     * @var GenUtil
     */
    protected $genUtil;

    /* ******************** METHODS ******************** */

    /**
     * Constructor
     * @param GenUtil $genUtil
     */
    public function __construct(GenUtil $genUtil)
    {
        $this->genUtil  = $genUtil;
    }

    /**
     * Utility form helper that renders a label (if it exists), an element, hint, description and errors
     * @param ElementInterface $element
     * @param string|null $formType
     * @param array $displayConfig
     * @return string
     */
    public function render(ElementInterface $element, $formType = null, array $displayConfig = array())
    {
        $escapeHtmlHelper    = $this->getEscapeHtmlHelper();
        $elementHelper       = $this->getElementHelper();
        $elementErrorsHelper = $this->getElementErrorsHelper();
        $hintHelper          = $this->getHintHelper();
        $descriptionHelper   = $this->getDescriptionHelper();

        $label               = $element->getLabel();
        $elementString       = $elementHelper->render($element, $formType, $displayConfig);
        $hint                = $hintHelper->render($element);
        $description         = $descriptionHelper->render($element);
        $elementErrors       = $elementErrorsHelper->render($element);

        if ($formType == FormUtil::FORM_TYPE_HORIZONTAL || $formType == FormUtil::FORM_TYPE_VERTICAL) {
            $controlGroupHelper     = $this->getControlGroupHelper();
            $controlsHelper         = $this->getControlsHelper();
            $controlGroupOpen       = $controlGroupHelper->openTag($element);
            $controlGroupClose      = $controlGroupHelper->closeTag();
            $controlsOpen           = $controlsHelper->openTag($element);
            $controlsClose          = $controlsHelper->closeTag();
        } else {
            $controlGroupOpen       = '';
            $controlGroupClose      = '';
            $controlsOpen           = '';
            $controlsClose          = '';
        }

        if (!empty($label)) {
            $label = $escapeHtmlHelper($label);
            $labelAttributes = $element->getLabelAttributes();

            if (empty($labelAttributes)) {
                $labelAttributes = $this->labelAttributes;
            }
            $labelAttributes    = $this->genUtil->addWordToArrayItem('control-label', $labelAttributes, 'class');
            $element->setLabelAttributes($labelAttributes);


            // Multicheckbox elements have to be handled differently as the HTML standard does not allow nested
            // labels. The semantic way is to group them inside a fieldset
            $type = $element->getAttribute('type');
            if ($type === 'multi_checkbox' || $type === 'multicheckbox' || $type === 'radio') {
                $markup = sprintf(
                    '<fieldset><legend>%s</legend>%s</fieldset>',
                    $label,
                    $elementString);
            } else {
                $labelHelper         = $this->getLabelHelper();
                if ($element->hasAttribute('id')) {
                    $labelOpen = $labelHelper($element);
                    $labelClose = '';
                    $label = '';
                } else {
                    $labelOpen  = $labelHelper->openTag($labelAttributes);
                    $labelClose = $labelHelper->closeTag();
                }

                switch ($this->labelPosition) {
                    case self::LABEL_PREPEND:
                        $markup = $controlGroupOpen
                                . $labelOpen
                                . $label
                                . $controlsOpen
                                . $elementString
                                . $hint
                                . $description
                                . $elementErrors
                                . $controlsClose
                                . $labelClose
                            . $controlGroupClose;
                        break;
                    case self::LABEL_APPEND:
                    default:
                        $markup = $labelOpen . $elementString . $label . $labelClose . $elementErrors;
                        break;
                }
            }
        } else {
            $markup = $controlGroupOpen
                    . $controlsOpen
                    . $elementString
                    . $hint
                    . $description
                    . $elementErrors
                    . $controlsClose
                    . $controlGroupClose;
        }

        return $markup;
    }

    /**
     * Invoke helper as a function
     * Proxies to {@link render()}.
     * @param null|ElementInterface $element
     * @param string|null $formType
     * @param array $displayConfig
     * @return string|FormRowTwb
     */
    public function __invoke(ElementInterface $element = null, $formType = null, array $displayConfig = array()) {
        if (!$element) {
            return $this;
        }
        return $this->render($element, $formType, $displayConfig);
    }

    /**
     * Set the label position
     *
     * @param $labelPosition
     * @return FormRow
     * @throws \Zend\Form\Exception\InvalidArgumentException
     */
    public function setLabelPosition($labelPosition)
    {
        $labelPosition = strtolower($labelPosition);
        if (!in_array($labelPosition, array(self::LABEL_APPEND, self::LABEL_PREPEND))) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects either %s::LABEL_APPEND or %s::LABEL_PREPEND; received "%s"',
                __METHOD__,
                __CLASS__,
                __CLASS__,
                (string) $labelPosition
            ));
        }
        $this->labelPosition = $labelPosition;

        return $this;
    }

    /**
     * Get the label position
     *
     * @return string
     */
    public function getLabelPosition()
    {
        return $this->labelPosition;
    }

    /**
     * Set the attributes for the row label
     *
     * @param  array $labelAttributes
     * @return FormRow
     */
    public function setLabelAttributes($labelAttributes)
    {
        $this->labelAttributes = $labelAttributes;
        return $this;
    }

    /**
     * Get the attributes for the row label
     *
     * @return array
     */
    public function getLabelAttributes()
    {
        return $this->labelAttributes;
    }

    /**
     * Retrieve the FormLabelTwb helper
     * @return FormLabelTwb
     * @throws \DluTwBootstrap\Form\Exception\UnsupportedHelperTypeException
     */
    protected function getLabelHelper()
    {
        if (!$this->labelHelper) {
            if (method_exists($this->view, 'plugin')) {
                $this->labelHelper = $this->view->plugin('form_label_twb');
            }
            if (!$this->labelHelper instanceof FormLabelTwb) {
                throw new UnsupportedHelperTypeException('Label helper (FormLabelTwb) unavailable or unsupported type.');
            }
        }
        return $this->labelHelper;
    }

    /**
     * Retrieve the FormElementTwb helper
     * @return FormElementTwb
     * @throws \DluTwBootstrap\Form\Exception\UnsupportedHelperTypeException
     */
    protected function getElementHelper()
    {
        if (!$this->elementHelper) {
            if (method_exists($this->view, 'plugin')) {
                $this->elementHelper = $this->view->plugin('form_element_twb');
            }
            if (!$this->elementHelper instanceof FormElementTwb) {
                throw new UnsupportedHelperTypeException('Element helper (FormElementTwb) unavailable or unsupported type.');
            }
        }
        return $this->elementHelper;
    }

    /**
     * Retrieve the FormElementErrorsTwb helper
     * @return FormElementErrorsTwb
     * @throws \DluTwBootstrap\Form\Exception\UnsupportedHelperTypeException
     */
    protected function getElementErrorsHelper()
    {
        if (!$this->elementErrorsHelper) {
            if (method_exists($this->view, 'plugin')) {
                $this->elementErrorsHelper = $this->view->plugin('form_element_errors_twb');
            }
            if (!$this->elementErrorsHelper instanceof FormElementErrorsTwb) {
                throw new UnsupportedHelperTypeException('Element errors helper (FormElementErrorsTwb) unavailable or unsupported type.');
            }
        }
        return $this->elementErrorsHelper;
    }

    /**
     * Retrieve the FormHintTwb helper
     * @return FormHintTwb
     * @throws \DluTwBootstrap\Form\Exception\UnsupportedHelperTypeException
     */
    protected function getHintHelper()
    {
        if (!$this->hintHelper) {
            if (method_exists($this->view, 'plugin')) {
                $this->hintHelper = $this->view->plugin('form_hint_twb');
            }
            if (!$this->hintHelper instanceof FormHintTwb) {
                throw new UnsupportedHelperTypeException('Hint helper (FormHintTwb) unavailable or unsupported type.');
            }
        }
        return $this->hintHelper;
    }

    /**
     * Retrieve the FormDescriptionTwb helper
     * @return FormDescriptionTwb
     * @throws \DluTwBootstrap\Form\Exception\UnsupportedHelperTypeException
     */
    protected function getDescriptionHelper()
    {
        if (!$this->descriptionHelper) {
            if (method_exists($this->view, 'plugin')) {
                $this->descriptionHelper = $this->view->plugin('form_description_twb');
            }
            if (!$this->descriptionHelper instanceof FormDescriptionTwb) {
                throw new UnsupportedHelperTypeException('Description helper (FormDescriptionTwb) unavailable or unsupported type.');
            }
        }
        return $this->descriptionHelper;
    }

    /**
     * Retrieve the FormControlGroupTwb helper
     * @return FormControlGroupTwb
     * @throws \DluTwBootstrap\Form\Exception\UnsupportedHelperTypeException
     */
    protected function getControlGroupHelper()
    {
        if (!$this->controlGroupHelper) {
            if (method_exists($this->view, 'plugin')) {
                $this->controlGroupHelper = $this->view->plugin('form_control_group_twb');
            }
            if (!$this->controlGroupHelper instanceof FormControlGroupTwb) {
                throw new UnsupportedHelperTypeException('Control group helper (FormControlGroupTwb) unavailable or unsupported type.');
            }
        }
        return $this->controlGroupHelper;
    }

    /**
     * Retrieve the FormControlsTwb helper
     * @return FormControlsTwb
     * @throws \DluTwBootstrap\Form\Exception\UnsupportedHelperTypeException
     */
    protected function getControlsHelper()
    {
        if (!$this->controlsHelper) {
            if (method_exists($this->view, 'plugin')) {
                $this->controlsHelper = $this->view->plugin('form_controls_twb');
            }
            if (!$this->controlsHelper instanceof FormControlsTwb) {
                throw new UnsupportedHelperTypeException('Controls helper (FormControlsTwb) unavailable or unsupported type.');
            }
        }
        return $this->controlsHelper;
    }

}