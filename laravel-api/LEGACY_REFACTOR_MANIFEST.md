# سجل إزالة النظام القديم

تمت مراجعة الملفات التي كانت تربط طبقات **Application** و**Domain** مباشرةً بـ Eloquent Models. قبل التعديل، كان الفحص يكشف **53 Use Case** و**5 Domain Contracts** وملفات Domain إضافية. بعد التعديل أصبح الفحص يكشف صفر مخالفات في جميع الفئات التالية:

| الفحص | قبل | بعد |
|---|---:|---:|
| Controller يصل مباشرة إلى Persistence | 0 | 0 |
| Application يعتمد على Infrastructure أو Presentation أو Database | 0 | 0 |
| Application يستورد `App\Models` | 53 | 0 |
| Domain يعتمد على طبقات خارجية | 0 | 0 |
| Domain يستورد `App\Models` | 6 | 0 |
| Contracts تستورد `App\Models` | 5 | 0 |

## الملفات المعدلة

### Auth وCatalog

- `app/Modules/Auth/Application/UseCases/AuthenticateUser.php`
- `app/Modules/Auth/Application/UseCases/AuthorizeUser.php`
- `app/Modules/Auth/Application/UseCases/ChangePassword.php`
- `app/Modules/Auth/Application/UseCases/GetCurrentUser.php`
- `app/Modules/Auth/Application/UseCases/LoginUser.php`
- `app/Modules/Auth/Application/UseCases/RegisterUser.php`
- `app/Modules/Auth/Presentation/Http/Controllers/AuthController.php`
- `app/Modules/Catalog/Application/UseCases/Attributes/CreateAttribute.php`
- `app/Modules/Catalog/Application/UseCases/Attributes/CreateAttributeValue.php`
- `app/Modules/Catalog/Application/UseCases/Attributes/GetAttribute.php`
- `app/Modules/Catalog/Application/UseCases/Attributes/GetAttributeValue.php`
- `app/Modules/Catalog/Application/UseCases/Brands/CreateBrand.php`
- `app/Modules/Catalog/Application/UseCases/Brands/GetBrand.php`
- `app/Modules/Catalog/Application/UseCases/Categories/CreateCategory.php`
- `app/Modules/Catalog/Application/UseCases/Categories/GetCategory.php`
- `app/Modules/Catalog/Application/UseCases/Products/CreateProduct.php`
- `app/Modules/Catalog/Application/UseCases/Products/CreateProductVariant.php`
- `app/Modules/Catalog/Application/UseCases/Products/GetProduct.php`
- `app/Modules/Catalog/Application/UseCases/Products/GetProductVariant.php`
- `app/Modules/Catalog/Application/UseCases/Products/UpdateProduct.php`
- `app/Modules/Catalog/Application/UseCases/Products/UpdateProductVariant.php`
- `app/Modules/Catalog/Infrastructure/Persistence/EloquentProductRepository.php`

### Customer

- `app/Modules/Customer/Application/UseCases/AddCartItem.php`
- `app/Modules/Customer/Application/UseCases/ClearCart.php`
- `app/Modules/Customer/Application/UseCases/GetCart.php`
- `app/Modules/Customer/Application/UseCases/GetCustomerProfile.php`
- `app/Modules/Customer/Application/UseCases/GetDefaultCustomerAddress.php`
- `app/Modules/Customer/Application/UseCases/ManageCustomerAddress.php`
- `app/Modules/Customer/Application/UseCases/ManageCustomerCart.php`
- `app/Modules/Customer/Application/UseCases/ManageCustomerNotifications.php`
- `app/Modules/Customer/Application/UseCases/RemoveCartItem.php`
- `app/Modules/Customer/Application/UseCases/UpdateCartItem.php`
- `app/Modules/Customer/Application/UseCases/UpdateCustomerProfile.php`
- `app/Modules/Customer/Domain/Contracts/AddressRepositoryInterface.php`
- `app/Modules/Customer/Domain/Contracts/CartRepositoryInterface.php`
- `app/Modules/Customer/Domain/Contracts/CustomerRepositoryInterface.php`
- `app/Modules/Customer/Infrastructure/Persistence/EloquentAddressRepository.php`
- `app/Modules/Customer/Infrastructure/Persistence/EloquentCartRepository.php`
- `app/Modules/Customer/Infrastructure/Persistence/EloquentCustomerNotificationRepository.php`
- `app/Modules/Customer/Infrastructure/Persistence/EloquentCustomerRepository.php`

### Inventory وOrder وPayment

- `app/Modules/Inventory/Application/UseCases/AdjustInventory.php`
- `app/Modules/Order/Application/UseCases/CancelOrder.php`
- `app/Modules/Order/Application/UseCases/Checkout.php`
- `app/Modules/Order/Application/UseCases/GetCustomerOrder.php`
- `app/Modules/Order/Application/UseCases/GetOrder.php`
- `app/Modules/Order/Application/UseCases/ListCustomerOrders.php`
- `app/Modules/Order/Application/UseCases/UpdateOrderStatus.php`
- `app/Modules/Payment/Application/UseCases/ConfirmPayment.php`
- `app/Modules/Payment/Application/UseCases/CreatePayment.php`
- `app/Modules/Payment/Application/UseCases/RefundPayment.php`

### Settings وShipping وStaff

- `app/Modules/Settings/Application/UseCases/GetSettingRecord.php`
- `app/Modules/Settings/Application/UseCases/GetSettingsByGroup.php`
- `app/Modules/Settings/Application/UseCases/UpdateSetting.php`
- `app/Modules/Settings/Domain/ValueObjects/SettingData.php`
- `app/Modules/Shipping/Application/UseCases/CreateShipment.php`
- `app/Modules/Shipping/Application/UseCases/CreateShippingMethod.php`
- `app/Modules/Shipping/Application/UseCases/GetShippingMethod.php`
- `app/Modules/Shipping/Application/UseCases/UpdateShipmentStatus.php`
- `app/Modules/Shipping/Application/UseCases/UpdateShippingMethod.php`
- `app/Modules/Staff/Application/UseCases/CreateStaff.php`
- `app/Modules/Staff/Application/UseCases/DeleteStaff.php`
- `app/Modules/Staff/Application/UseCases/GetStaff.php`
- `app/Modules/Staff/Application/UseCases/RecordStaffAudit.php`
- `app/Modules/Staff/Application/UseCases/UpdateStaff.php`
- `app/Modules/Staff/Domain/Contracts/AuditLogRepositoryInterface.php`
- `app/Modules/Staff/Domain/Contracts/StaffRepositoryInterface.php`
- `app/Modules/Staff/Infrastructure/Persistence/EloquentAuditLogRepository.php`
- `app/Modules/Staff/Infrastructure/Persistence/EloquentStaffRepository.php`

## أسلوب التصحيح

تم توسيع عقود Domain لتعيد `object` أو قيمًا مجردة بدل ربطها بـ Eloquent Model. تم تعديل Use Cases لتتعامل مع العقود فقط. بقيت معرفة Eloquent داخل Infrastructure، وهو المكان المسموح لها. كما تم توسيع توقيعات Infrastructure المتوافقة مع العقود دون نقل منطق قاعدة البيانات إلى Application.
