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

namespace Shopware\Bundle\StoreFrontBundle\Price;

use Doctrine\DBAL\Connection;
use Shopware\Framework\Struct\FieldHelper;
use Shopware\Context\TranslationContext;
use Shopware\Bundle\StoreFrontBundle\CustomerGroup\CustomerGroup;

/**
 * @category  Shopware
 *
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class GraduatedPricesGateway
{
    /**
     * @var PriceHydrator
     */
    private $priceHydrator;

    /**
     * The FieldHelper class is used for the
     * different table column definitions.
     *
     * This class helps to select each time all required
     * table data for the store front.
     *
     * Additionally the field helper reduce the work, to
     * select in a second step the different required
     * attribute tables for a parent table.
     *
     * @var \Shopware\Framework\Struct\FieldHelper
     */
    private $fieldHelper;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection    $connection
     * @param FieldHelper   $fieldHelper
     * @param PriceHydrator $priceHydrator
     */
    public function __construct(
        Connection $connection,
        FieldHelper $fieldHelper,
        PriceHydrator $priceHydrator
    ) {
        $this->connection = $connection;
        $this->priceHydrator = $priceHydrator;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @param \Shopware\Bundle\StoreFrontBundle\Product\ListProduct[] $products
     * @param TranslationContext                                      $context
     * @param CustomerGroup                                           $customerGroup
     *
     * @return array indexed by the product order number, each array element contains a Struct\Product\PriceRule array
     */
    public function getList($products, TranslationContext $context, CustomerGroup $customerGroup)
    {
        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product->getVariantId();
        }
        $ids = array_unique($ids);

        $query = $this->connection->createQueryBuilder();

        $query->select($this->fieldHelper->getPriceFields());
        $query->addSelect('variants.ordernumber as number');

        $query->from('s_articles_prices', 'price')
            ->innerJoin('price', 's_articles_details', 'variants', 'variants.id = price.articledetailsID')
            ->leftJoin('price', 's_articles_prices_attributes', 'priceAttribute', 'priceAttribute.priceID = price.id')
            ->where('price.articledetailsID IN (:products)')
            ->andWhere('price.pricegroup = :customerGroup')
            ->orderBy('price.articledetailsID', 'ASC')
            ->addOrderBy('price.from', 'ASC')
            ->setParameter(':products', $ids, Connection::PARAM_INT_ARRAY)
            ->setParameter(':customerGroup', $customerGroup->getKey());

        $this->fieldHelper->addPriceTranslation($query, $context);

        /** @var $statement \Doctrine\DBAL\Driver\ResultStatement */
        $statement = $query->execute();

        $data = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $prices = [];
        foreach ($data as $row) {
            $product = $row['number'];
            $prices[$product][] = $this->priceHydrator->hydratePriceRule($row);
        }

        return $prices;
    }
}