<?php

namespace Fenom;


class MultiPathsProviderTest extends \PHPUnit_Framework_TestCase {

    public function getMTime($tpl) {
        return filemtime(PC_TEST_DIR."/templates/$tpl");
    }

    public function assertTplsList($expected, MultiPathsProvider $provider) {
        $list = $provider->getList('tpl');
        sort($list);
        sort($expected);
        $this->assertEquals($expected, $list);
    }

    public function assertProvidedTpl($dir, MultiPathsProvider $provider, $tpl) {
        $tpl_file = PC_TEST_DIR."/templates/{$dir}/{$tpl}";
        $this->assertTrue($provider->templateExists($tpl));
        $this->assertEquals(file_get_contents($tpl_file), $provider->getSource($tpl, $time));
        clearstatcache(true);
        $this->assertEquals(filemtime($tpl_file), $time);
    }

    public function testPaths() {
        $provider = new MultiPathsProvider(PC_TEST_DIR.'/templates/one');

        $this->assertProvidedTpl('one', $provider, 'one.tpl');
        $this->assertProvidedTpl('one', $provider, 'two.tpl');
        $this->assertFalse($provider->templateExists('three.tpl'));

        $this->assertEquals([PC_TEST_DIR.'/templates/one'], $provider->getPaths());
        $this->assertTplsList(['one.tpl', 'two.tpl'], $provider);
        $this->assertTrue($provider->verify([
            'one.tpl' => $this->getMTime('one/one.tpl'),
            'two.tpl' => $this->getMTime('one/two.tpl')
        ]));

        $provider->addPath(PC_TEST_DIR.'/templates/two');

        $this->assertProvidedTpl('one', $provider, 'one.tpl');
        $this->assertProvidedTpl('one', $provider, 'two.tpl');
        $this->assertProvidedTpl('two', $provider, 'three.tpl');

        $this->assertEquals([PC_TEST_DIR.'/templates/one', PC_TEST_DIR.'/templates/two'], $provider->getPaths());
        $this->assertTplsList(['one.tpl', 'two.tpl', 'three.tpl'], $provider);
        $this->assertTrue($provider->verify([
            'one.tpl'   => $this->getMTime('one/one.tpl'),
            'two.tpl'   => $this->getMTime('one/two.tpl'),
            'three.tpl' => $this->getMTime('two/three.tpl'),
        ]));

        $provider->prependPath(PC_TEST_DIR.'/templates/two');

        $this->assertProvidedTpl('one', $provider, 'one.tpl');
        $this->assertProvidedTpl('two', $provider, 'two.tpl');
        $this->assertProvidedTpl('two', $provider, 'three.tpl');
        $this->assertEquals(
            [PC_TEST_DIR.'/templates/two', PC_TEST_DIR.'/templates/one', PC_TEST_DIR.'/templates/two'],
            $provider->getPaths()
        );
        $this->assertTplsList(['one.tpl', 'two.tpl', 'three.tpl'], $provider);
        $this->assertTrue($provider->verify([
            'one.tpl'   => $this->getMTime('one/one.tpl'),
            'two.tpl'   => $this->getMTime('two/two.tpl'),
            'three.tpl' => $this->getMTime('two/three.tpl'),
        ]));
    }
}