<?php

use Flagsmith\Engine\Identities\Traits\TraitModel;
use PHPUnit\Framework\TestCase;

class IdentitySchemaTest extends TestCase
{
    public function getTraitKeysAndValues()
    {
        return [
            ['key', 'value'],
            ['key1', 21],
            ['key1', null],
            ['key1', 11.2],
            ['key1', true],
        ];
    }

    /**
     * @dataProvider getTraitKeysAndValues
     */
    public function testTraitSchemaLoad($traitKey, $traitValue)
    {
        $traitModel = (new TraitModel())
            ->withTraitKey($traitKey)
            ->withTraitValue($traitValue);

        $this->assertEquals($traitModel->getTraitKey(), $traitKey);
        $this->assertEquals($traitModel->getTraitValue(), $traitValue);
    }

    public function getTraitModels()
    {
        return [
            [(new TraitModel())->withTraitKey('key')
                ->withTraitValue('value')],
            [(new TraitModel())->withTraitKey('key')
                ->withTraitValue(21)],
            [(new TraitModel())->withTraitKey('key1')
                ->withTraitValue(null)],
            [(new TraitModel())->withTraitKey('key1')
                ->withTraitValue(11.2)],
            [(new TraitModel())->withTraitKey('key1')
                ->withTraitValue(true)],
        ];
    }

    /**
     * @dataProvider getTraitModels
     */
    public function testTraitSchemaDump($traitModel)
    {
        $traitDict = json_decode(json_encode($traitModel));

        $this->assertEquals($traitModel->getTraitKey(), $traitDict->trait_key);
        $this->assertEquals($traitModel->getTraitValue(), $traitDict->trait_value);
    }
}
