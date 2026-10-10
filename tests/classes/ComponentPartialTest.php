<?php

namespace Cms\Tests\Classes;

use Cms\Classes\ComponentBase;
use Cms\Classes\ComponentPartial;
use Cms\Classes\Partial;
use Cms\Classes\Theme;
use Config;
use RuntimeException;
use System\Tests\Bootstrap\TestCase;
use Winter\Storm\Halcyon\Model as HalcyonModel;

class ComponentPartialTest extends TestCase
{
    protected $origThemePath;

    protected $origCacheManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->origThemePath = Config::get('cms.themesPath');
        Config::set('cms.themesPath', '/modules/cms/tests/fixtures/themes');

        $this->origCacheManager = HalcyonModel::getCacheManager();
    }

    protected function makeComponent(string $alias): ComponentBase
    {
        $component = new class extends ComponentBase {
            public function componentDetails()
            {
                return ['name' => 'Test', 'description' => 'Test component'];
            }
        };
        $component->alias = $alias;

        return $component;
    }

    public function tearDown(): void
    {
        HalcyonModel::setCacheManager($this->origCacheManager);
        Config::set('cms.themesPath', $this->origThemePath);

        parent::tearDown();
    }

    public function testLoadOverrideCachedFindsThemeOverride()
    {
        $theme = Theme::load('test');
        $component = $this->makeComponent('overRide1');

        $partial = ComponentPartial::loadOverrideCached($theme, $component, 'default');

        $this->assertInstanceOf(Partial::class, $partial);
        $this->assertEquals('override1/default.htm', $partial->getFileName());
    }

    public function testLoadOverrideCachedSkipsThemeWithoutOverrideDirectory()
    {
        $theme = Theme::load('test');
        $component = $this->makeComponent('noOverrides');

        // A theme lookup would ask Halcyon for its cache, so fail if one runs.
        HalcyonModel::setCacheManager(new class {
            public function driver($name = null)
            {
                throw new RuntimeException('The theme was searched for an override that cannot exist.');
            }
        });

        $this->assertNull(ComponentPartial::loadOverrideCached($theme, $component, 'default'));
    }
}
