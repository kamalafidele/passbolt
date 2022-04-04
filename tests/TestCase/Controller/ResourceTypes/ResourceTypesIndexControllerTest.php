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

namespace App\Test\TestCase\Controller\ResourceTypes;

use App\Test\Lib\AppIntegrationTestCase;
use App\Test\Lib\Model\ResourceTypesModelTrait;

class ResourceTypesIndexControllerTest extends AppIntegrationTestCase
{
    use ResourceTypesModelTrait;

    public $fixtures = ['app.Base/Users', 'app.Base/Roles', 'app.Base/ResourceTypes'];

    public function testResourceTypesIndex_Success()
    {
        $this->authenticateAs('ada');
        $this->getJson('/resource-types.json?api-version=2');
        $this->assertSuccess();
        $this->assertGreaterThan(1, count($this->_responseJsonBody));
        $this->assertResourceTypeAttributes($this->_responseJsonBody[0]);
    }

    public function testResourceTypesIndex_ErrorNotAuthenticated()
    {
        $this->getJson('/resource-types.json');
        $this->assertAuthenticationError();
    }
}
