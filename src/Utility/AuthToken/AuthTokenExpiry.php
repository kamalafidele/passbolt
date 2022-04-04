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
 * @since         3.0.0
 */
namespace App\Utility\AuthToken;

use App\Model\Table\AuthenticationTokensTable;
use Cake\Core\Configure;
use InvalidArgumentException;

class AuthTokenExpiry
{
    /**
     * @param string $tokenType Token type
     * @return string|null
     */
    public function getExpiryForTokenType(string $tokenType)
    {
        if (!in_array($tokenType, AuthenticationTokensTable::ALLOWED_TYPES)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid $tokenType `%s`. Must be one of `%s`.',
                    $tokenType,
                    implode(',', AuthenticationTokensTable::ALLOWED_TYPES)
                )
            );
        }

        $tokenTypeExpiry = Configure::read(sprintf('passbolt.auth.token.%s.expiry', $tokenType));

        return $tokenTypeExpiry ?? Configure::read('passbolt.auth.tokenExpiry');
    }
}
