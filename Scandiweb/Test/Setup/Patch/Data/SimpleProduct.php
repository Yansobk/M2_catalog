<?PHP
declare(strict_types=1);

namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class SimpleProduct implements DataPatchInterface
{
    /**
     * @var ProductInterfaceFactory
     */
    protected $productFactory;
    
    /**
     * @var State
     */
    protected $state;
    
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CategoryLinkManagementInterface
     */
    protected $categoryLink;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

     /**
     * @var SourceItemInterface
     */
    protected $sourceItem;

    /**
     * @param ProductInterfaceFactory $productFactory
     * @param State $state
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryLinkManagementInterface $categoryLink
     * @param StockRegistryInterface $stockRegistry
     * @param SourceItemInterface $sourceItem
     */
    public function __construct(
        ProductInterfaceFactory $productFactory,
        State $state,
        ProductRepositoryInterface $productRepository,
        CategoryLinkManagementInterface $categoryLink,
        StockRegistryInterface $stockRegistry,
        SourceItemInterface $sourceItem
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->categoryLink = $categoryLink;
        $this->stockRegistry = $stockRegistry;
        $this->sourceItem = $sourceItem;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $product = $this->productFactory->create();

        if (!$product->getIdBySku('P-13940')) {
            return;
        }
        $product->setTypeId(Type::TYPE_SIMPLE)
                ->setAttributeSetId(4)
                ->setName('Curved Monitor 27')
                ->setSku('P-13940')
                ->setUrlKey('curved-monitor-27-P13940')
                ->setPrice(389.99)
                ->setVisibility(Visibility::VISIBILITY_BOTH)
                ->setStatus(Status::STATUS_ENABLED);

        $this->sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $this->productRepository->save($product);

        $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
        $stockItem->setIsInStock(1);
        $stockItem->setQty(100);

        $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
        $this->categoryLink->assignProductToCategories($product->getSku(), [2]);

        return $this;
    }

    public function apply()
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $this->state->emulateAreaCode(Area::AREA_ADMINHTML, [$this, 'execute']);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}