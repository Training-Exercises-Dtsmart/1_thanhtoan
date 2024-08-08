<?php


namespace Api;

use \ApiTester;


use app\modules\models\User;
use Codeception\Util\HttpCode;

class CatagoryCest
{
    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }


    // Test category creation
    public function testCreateCategory(ApiTester $I)
    {
        $I->wantTo('create a new category');
        $I->sendPOST('/category/create', $this->categoryData);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['status' => true]);
        $I->seeResponseContains('Category created successfully');
    }

    // Test category creation with invalid data
    public function testCreateCategoryWithInvalidData(ApiTester $I)
    {
        $I->wantTo('fail to create a new category with invalid data');
        $I->sendPOST('/category/create', []);
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['status' => false]);
    }
}
