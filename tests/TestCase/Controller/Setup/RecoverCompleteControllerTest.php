<?php
declare(strict_types=1);

/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
namespace App\Test\TestCase\Controller\Setup;

use App\Model\Entity\AuthenticationToken;
use App\Test\Lib\AppIntegrationTestCase;
use App\Test\Lib\Model\AuthenticationTokenModelTrait;
use App\Test\Lib\Model\EmailQueueTrait;
use App\Utility\UuidFactory;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class RecoverCompleteControllerTest extends AppIntegrationTestCase
{
    use AuthenticationTokenModelTrait;
    use EmailQueueTrait;

    public $fixtures = ['app.Base/Users', 'app.Base/Profiles', 'app.Base/Gpgkeys', 'app.Base/Roles',];
    public $AuthenticationTokens;

    public function setUp(): void
    {
        $this->AuthenticationTokens = TableRegistry::getTableLocator()->get('AuthenticationTokens');
        $this->Users = TableRegistry::getTableLocator()->get('Users');
        $this->Gpgkeys = TableRegistry::getTableLocator()->get('Gpgkeys');
        parent::setUp();
    }

    /**
     * @group AN
     * @group recover
     * @group recoverComplete
     */
    public function testRecoverCompleteSuccess()
    {
        $logEnabled = Configure::read('passbolt.plugins.log.enabled');
        Configure::write('passbolt.plugins.log.enabled', true);
        $t = $this->AuthenticationTokens->generate(UuidFactory::uuid('user.id.ada'), AuthenticationToken::TYPE_RECOVER);
        $url = '/setup/recover/complete/' . UuidFactory::uuid('user.id.ada') . '.json';
        $armoredKey = file_get_contents(FIXTURES . DS . 'Gpgkeys' . DS . 'ada_public.key');
        $data = [
            'authenticationtoken' => [
                'token' => $t->token,
            ],
            'gpgkey' => [
                'armored_key' => $armoredKey,
            ],
        ];
        $this->postJson($url, $data);
        $this->assertSuccess();

        // Check that token is now inactive
        $t2 = $this->AuthenticationTokens->get($t->id);
        $this->assertFalse($t2->active);
        $this->assertEmailQueueIsEmpty();
        Configure::write('passbolt.plugins.log.enabled', $logEnabled);
    }

    /**
     * @group AN
     * @group recover
     * @group recoverComplete
     */
    public function testRecoverCompleteInvalidUserIdError()
    {
        $url = '/setup/recover/complete/nope.json';
        $data = [];
        $this->postJson($url, $data);
        $this->assertError(400, 'The user identifier should be a valid UUID.');
    }

    /**
     * @group AN
     * @group recover
     * @group recoverComplete
     */
    public function testRecoverCompleteInvalidUserTokenError()
    {
        $url = '/setup/recover/complete/' . UuidFactory::uuid('user.id.nope') . '.json';
        $data = [];
        $this->postJson($url, $data);
        $this->assertError(400, 'The user does not exist');
    }

    /**
     * @group AN
     * @group recover
     * @group recoverComplete
     */
    public function testRecoverCompleteInvalidAuthenticationTokenError()
    {
        $userId = UuidFactory::uuid('user.id.ada');
        $url = '/setup/recover/complete/' . $userId . '.json';
        $tokenExpired = $this->quickDummyAuthToken($userId, AuthenticationToken::TYPE_RECOVER, 'expired');
        $tokenInactive = $this->quickDummyAuthToken($userId, AuthenticationToken::TYPE_RECOVER, 'inactive');

        $fails = [
            'empty array' => [
                'data' => [],
                'message' => 'An authentication token should be provided.',
            ],
            'null' => [
                'data' => null,
                'message' => 'An authentication token should be provided.',
            ],
            'array with null' => [
                'data' => ['token' => null],
                'message' => 'An authentication token should be provided.',
            ],
            'int' => [
                'data' => ['token' => 100],
                'message' => 'The authentication token should be a valid UUID.',
            ],
            'string' => [
                'data' => ['token' => 'nope'],
                'message' => 'The authentication token should be a valid UUID.',
            ],
            'expired token' => [
                'data' => ['token' => $tokenExpired],
                'message' => 'The authentication token is not valid or has expired.',
            ],
            'inactive token' => [
                'data' => ['token' => $tokenInactive],
                'message' => 'The authentication token is not valid or has expired.',
            ],
        ];
        foreach ($fails as $caseName => $case) {
            $data = [
                'authenticationtoken' => $case['data'],
            ];
            $this->postJson($url, $data);
            $this->assertError(400, $case['message'], 'Issue with test case: ' . $caseName);
        }
    }

    /**
     * @group AN
     * @group recover
     * @group recoverComplete
     */
    public function testRecoverCompleteAuthenticationTokenTypeError()
    {
        $userId = UuidFactory::uuid('user.id.ada');
        $url = '/setup/recover/complete/' . $userId . '.json';
        $tokenWrongType = $this->quickDummyAuthToken($userId, AuthenticationToken::TYPE_LOGIN);

        $fails = [
            'wrong type token' => [
                'data' => ['token' => $tokenWrongType],
                'message' => 'The authentication token is not valid or has expired.',
            ],
        ];
        foreach ($fails as $caseName => $case) {
            $data = [
                'authenticationtoken' => $case['data'],
            ];
            $this->postJson($url, $data);
            $this->assertError(400, $case['message'], 'Issue with test case: ' . $caseName);
        }
    }

    /**
     * @group AN
     * @group recover
     * @group recoverComplete
     */
    public function testRecoverCompleteInvalidGpgkeyError()
    {
        $t = $this->AuthenticationTokens->generate(UuidFactory::uuid('user.id.ada'), AuthenticationToken::TYPE_RECOVER);
        $url = '/setup/recover/complete/' . UuidFactory::uuid('user.id.ada') . '.json';

        $armoredKey = file_get_contents(FIXTURES . DS . 'Gpgkeys' . DS . 'ada_public.key');
        $cutKey = substr($armoredKey, 0, strlen($armoredKey) / 2);
        $fails = [
            'empty array' => [
                'data' => [],
                'message' => 'An OpenPGP key must be provided.',
            ],
            'null' => [
                'data' => null,
                'message' => 'An OpenPGP key must be provided.',
            ],
            'array with null' => [
                'data' => ['armored_key' => null],
                'message' => 'An OpenPGP key must be provided.',
            ],
            'int' => [
                'data' => ['armored_key' => 100],
                'message' => 'A valid OpenPGP key must be provided.',
            ],
            'string' => [
                'data' => ['armored_key' => 'nope'],
                'message' => 'A valid OpenPGP key must be provided.',
            ],
            'partial key' => [
                'data' => ['armored_key' => $cutKey],
                'message' => 'A valid OpenPGP key must be provided.',
            ],
        ];
        foreach ($fails as $caseName => $case) {
            $data = [
            'authenticationtoken' => [
                'token' => $t->token,
            ],
            'gpgkey' => $case['data'],
            ];
        }
        $this->postJson($url, $data);
        $this->assertError(400, $case['message'], 'Issue with case: ' . $caseName);
    }

    /**
     * @group AN
     * @group recover
     * @group recoverComplete
     */
    public function testRecoverCompleteDeletedUserError()
    {
        $url = '/setup/recover/complete/' . UuidFactory::uuid('user.id.sofia') . '.json';
        $this->postJson($url, []);
        $this->assertError(400, 'The user does not exist');
    }

    /**
     * @group AN
     * @group recover
     * @group recoverComplete
     */
    public function testRecoverCompleteInactiveUserError()
    {
        $url = '/setup/recover/complete/' . UuidFactory::uuid('user.id.ruth') . '.json';
        $this->postJson($url, []);
        $this->assertError(400, 'The user does not exist');
    }
}
