<?php

namespace OffbeatWP\Components;

use Doctrine\Common\Cache\ArrayCache;
use OffbeatWP\Form\Form;
use OffbeatWP\Form\Fields\Select;
use OffbeatWP\Contracts\View;
use OffbeatWP\Layout\ContextInterface;
use OffbeatWP\Views\ViewableTrait;

abstract class AbstractComponent
{
    use ViewableTrait;

    /**
     * @var View
     */
    public $view;

    /**
     * @var null|ContextInterface
     */
    protected $context;

    public $form = null;

    public function __construct(View $view, ContextInterface $context = null)
    {
        $this->view = $view;
        $this->context = $context;

        if (!offbeat()->container->has('componentCache')) {
            // just a simple light weight cache if none is set
            offbeat()->container->set('componentCache', new ArrayCache());
        }
    }

    /**
     * Can this component be rendered?
     *
     * @return bool
     */
    public function isRenderable()
    {
        return true;
    }

    /**
     * Render the component.
     *
     * @param $settings
     * @return string
     */
    public function renderComponent($settings)
    {
        if (!$this->isRenderable()) {
            return '';
        }

        $cachedId = $this->getCacheId($settings);
        $object = $this->getCachedComponent($cachedId);
        if ($object !== false) {
            return $object;
        }
        if ($this->context) {
            $this->context->initContext();
        }
        $render = apply_filters('offbeat.component.render', $this->render($settings), $this);
        return $this->setCachedObject($cachedId, $render);
    }

    protected function getCacheId($settings)
    {
        $prefix = $this->context ? $this->context->getCacheId() : '';
        return md5($prefix . get_class($this) . json_encode($settings));
    }

    protected function getCachedComponent($id)
    {
        $object = $this->getCachedObject($id);
        if ($object !== false) {
            return $object;
        }
        return false;
    }

    protected function getCachedObject($id)
    {
        return container('componentCache')->fetch($id);
    }

    protected function setCachedObject(string $id, $object)
    {

        container('componentCache')->save($id, (string)$object, 60);
        return (string)$object;
    }

    public static function supports($service)
    {
        if (!method_exists(get_called_class(), 'settings')) return false;

        $componentSettings = static::settings();

        if (!array_key_exists('supports', $componentSettings) || !in_array($service, $componentSettings['supports'])) return false;

        return true;
    }

    public static function getSetting($key)
    {
        if (!method_exists(get_called_class(), 'settings')) return false;

        $componentSettings = static::settings();

        return isset($componentSettings[$key]) ? $componentSettings[$key] : null;
    }

    public static function getName()
    {
        return static::getSetting('name');
    }

    public static function getSlug()
    {
        return static::getSetting('slug');
    }

    public static function getDescription()
    {
        return static::getSetting('description');
    }

    public static function getCategory()
    {
        return static::getSetting('category');
    }

    public function getViewsDirectory()
    {
        return $this->getDirectory() . '/views';
    }

    public function getDirectory()
    {
        $classInfo = new \ReflectionClass($this);

        return dirname($classInfo->getFileName());
    }

    public static function getForm()
    {
        if (!method_exists(get_called_class(), 'settings')) return [];

        $form = null;
        $settings = static::settings();

        if (isset($settings['form']))
            $form = $settings['form'];

        if (!($form instanceof Form)) {
            $form = new Form();
        }

        if (!empty($form) && $form instanceof Form && isset($settings['variations'])) {
            $form->addField(
                Select::make(
                    'variation',
                    __('Variation', 'offbeatwp')
                )->addOptions($settings['variations'])
            );
        }

        $form = apply_filters('offbeatwp/component/form', $form, static::class);

        return $form;
    }
}