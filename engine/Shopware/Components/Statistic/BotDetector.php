<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Components\Statistic;

use Enlight_Controller_Request_Request as Request;

class BotDetector implements BotDetectorInterface
{
    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @param \Shopware_Components_Config $config
     */
    public function __construct(\Shopware_Components_Config $config)
    {
        $this->config = $config;
    }

    public function isBotRequest(Request $request): bool
    {
        $blacklist = $this->config->get('botBlackList');

        $userAgent = $request->getHeader('USER_AGENT');

        $userAgent = preg_replace('/[^a-z]/', '', strtolower($userAgent));
        if (empty($userAgent)) {
            return false;
        }

        $bots = preg_replace('/[^a-z;]/', '', strtolower($blacklist));
        $bots = explode(';', $bots);

        return !empty($userAgent) && str_replace($bots, '', $userAgent) != $userAgent;
    }
}