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
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class SimpleProduct implements DataPatchInterface
{
    /**
     * @var ProductInterfaceFactory
     */
    protected ProductInterfaceFactory $productFactory;
    
    /**
     * @var State
     */
    protected State $state;
    
    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var CategoryLinkManagementInterface
     */
    protected CategoryLinkManagementInterface $categoryLink;

    /**
     * @var StockRegistryInterface
     */
    protected StockRegistryInterface $stockRegistry;

    /**
     * @var SourceItemInterfaceFactory
     */
    protected SourceItemInterfaceFactory $sourceItem;

    /**
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;

    /**
     * @var array
     */
    protected array $sourceItems = [];

    /**
     * @param ProductInterfaceFactory $productFactory
     * @param State $state
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryLinkManagementInterface $categoryLink
     * @param StockRegistryInterface $stockRegistry
     * @param SourceItemInterfaceFactory $sourceItem
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     */
    public function __construct(
        ProductInterfaceFactory $productFactory,
        State $state,
        ProductRepositoryInterface $productRepository,
        CategoryLinkManagementInterface $categoryLink,
        StockRegistryInterface $stockRegistry,
        SourceItemInterfaceFactory $sourceItem,
        SourceItemsSaveInterface $sourceItemsSaveInterface
    ) {
        $this->state = $state;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->categoryLink = $categoryLink;
        $this->stockRegistry = $stockRegistry;
        $this->sourceItem = $sourceItem;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        $this->state->emulateAreaCode(Area::AREA_ADMINHTML, [$this, 'execute']);
    }

    /**
     * @return void
     */
    public function execute(): void
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
        $this->productRepository->save($product);

        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setQuantity(100);
        $sourceItem->setSku($product->getSku());
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $this->sourceItems[] = $sourceItem;
        $this->sourceItemsSaveInterface->execute($this->sourceItems);

        $this->categoryLink->assignProductToCategories($product->getSku(), [2]);
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
