<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    'Attribute' => $vendorDir . '/symfony/polyfill-php80/Resources/stubs/Attribute.php',
    'CMBIRD_API_Handler_Zoho' => $baseDir . '/includes/classes/class-api-handler-zoho.php',
    'CMBIRD_Auth_Zoho' => $baseDir . '/includes/classes/class-auth-zoho.php',
    'CMBIRD_Categories_ZI' => $baseDir . '/includes/classes/zoho-inventory/class-categories.php',
    'CMBIRD_Common_Functions' => $baseDir . '/includes/classes/class-common.php',
    'CMBIRD_Contact_ZI' => $baseDir . '/includes/classes/zoho-inventory/class-users-contact.php',
    'CMBIRD_Email_Purchase_Order' => $baseDir . '/includes/classes/purchase-orders/class-cmbird-purchase-order-email.php',
    'CMBIRD_Image_ZI' => $baseDir . '/includes/classes/zoho-inventory/class-import-image.php',
    'CMBIRD_List_Items_API_Controller' => $baseDir . '/includes/classes/apis/class-commercebird-list-items-api-controller.php',
    'CMBIRD_Media_API_Controller' => $baseDir . '/includes/classes/apis/class-commercebird-media-api-controller.php',
    'CMBIRD_Metadata_API_Controller' => $baseDir . '/includes/classes/apis/class-commercebird-metadata-controller.php',
    'CMBIRD_Multicurrency_Zoho' => $baseDir . '/includes/classes/zoho-inventory/class-multi-currency.php',
    'CMBIRD_Order_Sync_ZI' => $baseDir . '/includes/classes/zoho-inventory/class-order-sync.php',
    'CMBIRD_PO_Admin_Manager' => $baseDir . '/includes/classes/purchase-orders/class-cmbird-purchase-admin.php',
    'CMBIRD_Pricelist_ZI' => $baseDir . '/includes/classes/zoho-inventory/class-import-price-list.php',
    'CMBIRD_Products_ZI' => $baseDir . '/includes/classes/zoho-inventory/class-import-items.php',
    'CMBIRD_Products_ZI_Export' => $baseDir . '/includes/classes/zoho-inventory/class-product.php',
    'CMBIRD_Purchase_Order' => $baseDir . '/includes/classes/purchase-orders/class-cmbird-purchase-order.php',
    'CMBIRD_REST_Shop_Purchase_Controller' => $baseDir . '/includes/classes/purchase-orders/class-cmbird-rest-shop-purchase-controller.php',
    'CommerceBird\\API\\Api' => $baseDir . '/includes/classes/apis/trait-api-permission.php',
    'CommerceBird\\API\\CMBird_APIs' => $baseDir . '/includes/classes/apis/class-api-for-cmbird.php',
    'CommerceBird\\API\\CreateOrderWebhook' => $baseDir . '/includes/classes/apis/class-api-for-woo-order.php',
    'CommerceBird\\API\\Exact' => $baseDir . '/includes/classes/apis/class-api-for-exact-webhooks.php',
    'CommerceBird\\API\\ProductWebhook' => $baseDir . '/includes/classes/apis/class-api-for-product-webhook.php',
    'CommerceBird\\API\\ShippingWebhook' => $baseDir . '/includes/classes/apis/class-api-for-shipping-status.php',
    'CommerceBird\\API\\Zoho' => $baseDir . '/includes/classes/apis/class-api-for-zoho-inventory.php',
    'CommerceBird\\Admin\\Actions\\Ajax\\AcfAjax' => $baseDir . '/admin/includes/Actions/Ajax/AcfAjax.php',
    'CommerceBird\\Admin\\Actions\\Ajax\\ExactOnlineAjax' => $baseDir . '/admin/includes/Actions/Ajax/ExactOnlineAjax.php',
    'CommerceBird\\Admin\\Actions\\Ajax\\ZohoCRMAjax' => $baseDir . '/admin/includes/Actions/Ajax/ZohoCRMAjax.php',
    'CommerceBird\\Admin\\Actions\\Ajax\\ZohoInventoryAjax' => $baseDir . '/admin/includes/Actions/Ajax/ZohoInventoryAjax.php',
    'CommerceBird\\Admin\\Actions\\Sync\\ExactOnlineSync' => $baseDir . '/admin/includes/Actions/Sync/ExactOnlineSync.php',
    'CommerceBird\\Admin\\Actions\\Sync\\ZohoCRMSync' => $baseDir . '/admin/includes/Actions/Sync/ZohoCRMSync.php',
    'CommerceBird\\Admin\\Cmbird_Acf' => $baseDir . '/admin/includes/Cmbird_Acf.php',
    'CommerceBird\\Admin\\Connectors\\CommerceBird' => $baseDir . '/admin/includes/Connectors/CommerceBird.php',
    'CommerceBird\\Admin\\Cors' => $baseDir . '/admin/includes/Cors.php',
    'CommerceBird\\Admin\\Template' => $baseDir . '/admin/includes/Template.php',
    'CommerceBird\\Admin\\Traits\\AjaxRequest' => $baseDir . '/admin/includes/Traits/AjaxRequest.php',
    'CommerceBird\\Admin\\Traits\\LogWriter' => $baseDir . '/admin/includes/Traits/LogWriter.php',
    'CommerceBird\\Admin\\Traits\\OptionStatus' => $baseDir . '/admin/includes/Traits/OptionStatus.php',
    'CommerceBird\\Admin\\Traits\\Singleton' => $baseDir . '/admin/includes/Traits/Singleton.php',
    'CommerceBird\\Cmbird_WC_API' => $baseDir . '/includes/classes/class-wc-api.php',
    'CommerceBird\\Plugin' => $baseDir . '/includes/classes/class-plugin.php',
    'CommerceBird\\ZCRM_Custom_Fields' => $baseDir . '/includes/classes/zoho-crm/class-zcrm-custom-fields.php',
    'Composer\\InstalledVersions' => $vendorDir . '/composer/InstalledVersions.php',
    'PHPCSStandards\\Composer\\Plugin\\Installers\\PHPCodeSniffer\\Plugin' => $vendorDir . '/dealerdirect/phpcodesniffer-composer-installer/src/Plugin.php',
    'PHPCSUtils\\AbstractSniffs\\AbstractArrayDeclarationSniff' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/AbstractSniffs/AbstractArrayDeclarationSniff.php',
    'PHPCSUtils\\BackCompat\\BCFile' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/BackCompat/BCFile.php',
    'PHPCSUtils\\BackCompat\\BCTokens' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/BackCompat/BCTokens.php',
    'PHPCSUtils\\BackCompat\\Helper' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/BackCompat/Helper.php',
    'PHPCSUtils\\Exceptions\\InvalidTokenArray' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Exceptions/InvalidTokenArray.php',
    'PHPCSUtils\\Exceptions\\TestFileNotFound' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Exceptions/TestFileNotFound.php',
    'PHPCSUtils\\Exceptions\\TestMarkerNotFound' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Exceptions/TestMarkerNotFound.php',
    'PHPCSUtils\\Exceptions\\TestTargetNotFound' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Exceptions/TestTargetNotFound.php',
    'PHPCSUtils\\Fixers\\SpacesFixer' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Fixers/SpacesFixer.php',
    'PHPCSUtils\\Internal\\Cache' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Internal/Cache.php',
    'PHPCSUtils\\Internal\\IsShortArrayOrList' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Internal/IsShortArrayOrList.php',
    'PHPCSUtils\\Internal\\IsShortArrayOrListWithCache' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Internal/IsShortArrayOrListWithCache.php',
    'PHPCSUtils\\Internal\\NoFileCache' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Internal/NoFileCache.php',
    'PHPCSUtils\\Internal\\StableCollections' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Internal/StableCollections.php',
    'PHPCSUtils\\TestUtils\\UtilityMethodTestCase' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/TestUtils/UtilityMethodTestCase.php',
    'PHPCSUtils\\Tokens\\Collections' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Tokens/Collections.php',
    'PHPCSUtils\\Tokens\\TokenHelper' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Tokens/TokenHelper.php',
    'PHPCSUtils\\Utils\\Arrays' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Arrays.php',
    'PHPCSUtils\\Utils\\Conditions' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Conditions.php',
    'PHPCSUtils\\Utils\\Context' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Context.php',
    'PHPCSUtils\\Utils\\ControlStructures' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/ControlStructures.php',
    'PHPCSUtils\\Utils\\FunctionDeclarations' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/FunctionDeclarations.php',
    'PHPCSUtils\\Utils\\GetTokensAsString' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/GetTokensAsString.php',
    'PHPCSUtils\\Utils\\Lists' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Lists.php',
    'PHPCSUtils\\Utils\\MessageHelper' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/MessageHelper.php',
    'PHPCSUtils\\Utils\\Namespaces' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Namespaces.php',
    'PHPCSUtils\\Utils\\NamingConventions' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/NamingConventions.php',
    'PHPCSUtils\\Utils\\Numbers' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Numbers.php',
    'PHPCSUtils\\Utils\\ObjectDeclarations' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/ObjectDeclarations.php',
    'PHPCSUtils\\Utils\\Operators' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Operators.php',
    'PHPCSUtils\\Utils\\Orthography' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Orthography.php',
    'PHPCSUtils\\Utils\\Parentheses' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Parentheses.php',
    'PHPCSUtils\\Utils\\PassedParameters' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/PassedParameters.php',
    'PHPCSUtils\\Utils\\Scopes' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Scopes.php',
    'PHPCSUtils\\Utils\\TextStrings' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/TextStrings.php',
    'PHPCSUtils\\Utils\\UseStatements' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/UseStatements.php',
    'PHPCSUtils\\Utils\\Variables' => $vendorDir . '/phpcsstandards/phpcsutils/PHPCSUtils/Utils/Variables.php',
    'PhpToken' => $vendorDir . '/symfony/polyfill-php80/Resources/stubs/PhpToken.php',
    'Psr\\Log\\AbstractLogger' => $vendorDir . '/psr/log/src/AbstractLogger.php',
    'Psr\\Log\\InvalidArgumentException' => $vendorDir . '/psr/log/src/InvalidArgumentException.php',
    'Psr\\Log\\LogLevel' => $vendorDir . '/psr/log/src/LogLevel.php',
    'Psr\\Log\\LoggerAwareInterface' => $vendorDir . '/psr/log/src/LoggerAwareInterface.php',
    'Psr\\Log\\LoggerAwareTrait' => $vendorDir . '/psr/log/src/LoggerAwareTrait.php',
    'Psr\\Log\\LoggerInterface' => $vendorDir . '/psr/log/src/LoggerInterface.php',
    'Psr\\Log\\LoggerTrait' => $vendorDir . '/psr/log/src/LoggerTrait.php',
    'Psr\\Log\\NullLogger' => $vendorDir . '/psr/log/src/NullLogger.php',
    'Stringable' => $vendorDir . '/symfony/polyfill-php80/Resources/stubs/Stringable.php',
    'Symfony\\Component\\VarDumper\\Caster\\AmqpCaster' => $vendorDir . '/symfony/var-dumper/Caster/AmqpCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\ArgsStub' => $vendorDir . '/symfony/var-dumper/Caster/ArgsStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\Caster' => $vendorDir . '/symfony/var-dumper/Caster/Caster.php',
    'Symfony\\Component\\VarDumper\\Caster\\ClassStub' => $vendorDir . '/symfony/var-dumper/Caster/ClassStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\ConstStub' => $vendorDir . '/symfony/var-dumper/Caster/ConstStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\CutArrayStub' => $vendorDir . '/symfony/var-dumper/Caster/CutArrayStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\CutStub' => $vendorDir . '/symfony/var-dumper/Caster/CutStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\DOMCaster' => $vendorDir . '/symfony/var-dumper/Caster/DOMCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\DateCaster' => $vendorDir . '/symfony/var-dumper/Caster/DateCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\DoctrineCaster' => $vendorDir . '/symfony/var-dumper/Caster/DoctrineCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\DsCaster' => $vendorDir . '/symfony/var-dumper/Caster/DsCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\DsPairStub' => $vendorDir . '/symfony/var-dumper/Caster/DsPairStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\EnumStub' => $vendorDir . '/symfony/var-dumper/Caster/EnumStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\ExceptionCaster' => $vendorDir . '/symfony/var-dumper/Caster/ExceptionCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\FiberCaster' => $vendorDir . '/symfony/var-dumper/Caster/FiberCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\FrameStub' => $vendorDir . '/symfony/var-dumper/Caster/FrameStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\GmpCaster' => $vendorDir . '/symfony/var-dumper/Caster/GmpCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\ImagineCaster' => $vendorDir . '/symfony/var-dumper/Caster/ImagineCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\ImgStub' => $vendorDir . '/symfony/var-dumper/Caster/ImgStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\IntlCaster' => $vendorDir . '/symfony/var-dumper/Caster/IntlCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\LinkStub' => $vendorDir . '/symfony/var-dumper/Caster/LinkStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\MemcachedCaster' => $vendorDir . '/symfony/var-dumper/Caster/MemcachedCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\MysqliCaster' => $vendorDir . '/symfony/var-dumper/Caster/MysqliCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\PdoCaster' => $vendorDir . '/symfony/var-dumper/Caster/PdoCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\PgSqlCaster' => $vendorDir . '/symfony/var-dumper/Caster/PgSqlCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\ProxyManagerCaster' => $vendorDir . '/symfony/var-dumper/Caster/ProxyManagerCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster' => $vendorDir . '/symfony/var-dumper/Caster/RdKafkaCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\RedisCaster' => $vendorDir . '/symfony/var-dumper/Caster/RedisCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster' => $vendorDir . '/symfony/var-dumper/Caster/ReflectionCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\ResourceCaster' => $vendorDir . '/symfony/var-dumper/Caster/ResourceCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\SplCaster' => $vendorDir . '/symfony/var-dumper/Caster/SplCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\StubCaster' => $vendorDir . '/symfony/var-dumper/Caster/StubCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\SymfonyCaster' => $vendorDir . '/symfony/var-dumper/Caster/SymfonyCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\TraceStub' => $vendorDir . '/symfony/var-dumper/Caster/TraceStub.php',
    'Symfony\\Component\\VarDumper\\Caster\\UuidCaster' => $vendorDir . '/symfony/var-dumper/Caster/UuidCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\XmlReaderCaster' => $vendorDir . '/symfony/var-dumper/Caster/XmlReaderCaster.php',
    'Symfony\\Component\\VarDumper\\Caster\\XmlResourceCaster' => $vendorDir . '/symfony/var-dumper/Caster/XmlResourceCaster.php',
    'Symfony\\Component\\VarDumper\\Cloner\\AbstractCloner' => $vendorDir . '/symfony/var-dumper/Cloner/AbstractCloner.php',
    'Symfony\\Component\\VarDumper\\Cloner\\ClonerInterface' => $vendorDir . '/symfony/var-dumper/Cloner/ClonerInterface.php',
    'Symfony\\Component\\VarDumper\\Cloner\\Cursor' => $vendorDir . '/symfony/var-dumper/Cloner/Cursor.php',
    'Symfony\\Component\\VarDumper\\Cloner\\Data' => $vendorDir . '/symfony/var-dumper/Cloner/Data.php',
    'Symfony\\Component\\VarDumper\\Cloner\\DumperInterface' => $vendorDir . '/symfony/var-dumper/Cloner/DumperInterface.php',
    'Symfony\\Component\\VarDumper\\Cloner\\Stub' => $vendorDir . '/symfony/var-dumper/Cloner/Stub.php',
    'Symfony\\Component\\VarDumper\\Cloner\\VarCloner' => $vendorDir . '/symfony/var-dumper/Cloner/VarCloner.php',
    'Symfony\\Component\\VarDumper\\Command\\Descriptor\\CliDescriptor' => $vendorDir . '/symfony/var-dumper/Command/Descriptor/CliDescriptor.php',
    'Symfony\\Component\\VarDumper\\Command\\Descriptor\\DumpDescriptorInterface' => $vendorDir . '/symfony/var-dumper/Command/Descriptor/DumpDescriptorInterface.php',
    'Symfony\\Component\\VarDumper\\Command\\Descriptor\\HtmlDescriptor' => $vendorDir . '/symfony/var-dumper/Command/Descriptor/HtmlDescriptor.php',
    'Symfony\\Component\\VarDumper\\Command\\ServerDumpCommand' => $vendorDir . '/symfony/var-dumper/Command/ServerDumpCommand.php',
    'Symfony\\Component\\VarDumper\\Dumper\\AbstractDumper' => $vendorDir . '/symfony/var-dumper/Dumper/AbstractDumper.php',
    'Symfony\\Component\\VarDumper\\Dumper\\CliDumper' => $vendorDir . '/symfony/var-dumper/Dumper/CliDumper.php',
    'Symfony\\Component\\VarDumper\\Dumper\\ContextProvider\\CliContextProvider' => $vendorDir . '/symfony/var-dumper/Dumper/ContextProvider/CliContextProvider.php',
    'Symfony\\Component\\VarDumper\\Dumper\\ContextProvider\\ContextProviderInterface' => $vendorDir . '/symfony/var-dumper/Dumper/ContextProvider/ContextProviderInterface.php',
    'Symfony\\Component\\VarDumper\\Dumper\\ContextProvider\\RequestContextProvider' => $vendorDir . '/symfony/var-dumper/Dumper/ContextProvider/RequestContextProvider.php',
    'Symfony\\Component\\VarDumper\\Dumper\\ContextProvider\\SourceContextProvider' => $vendorDir . '/symfony/var-dumper/Dumper/ContextProvider/SourceContextProvider.php',
    'Symfony\\Component\\VarDumper\\Dumper\\ContextualizedDumper' => $vendorDir . '/symfony/var-dumper/Dumper/ContextualizedDumper.php',
    'Symfony\\Component\\VarDumper\\Dumper\\DataDumperInterface' => $vendorDir . '/symfony/var-dumper/Dumper/DataDumperInterface.php',
    'Symfony\\Component\\VarDumper\\Dumper\\HtmlDumper' => $vendorDir . '/symfony/var-dumper/Dumper/HtmlDumper.php',
    'Symfony\\Component\\VarDumper\\Dumper\\ServerDumper' => $vendorDir . '/symfony/var-dumper/Dumper/ServerDumper.php',
    'Symfony\\Component\\VarDumper\\Exception\\ThrowingCasterException' => $vendorDir . '/symfony/var-dumper/Exception/ThrowingCasterException.php',
    'Symfony\\Component\\VarDumper\\Server\\Connection' => $vendorDir . '/symfony/var-dumper/Server/Connection.php',
    'Symfony\\Component\\VarDumper\\Server\\DumpServer' => $vendorDir . '/symfony/var-dumper/Server/DumpServer.php',
    'Symfony\\Component\\VarDumper\\Test\\VarDumperTestTrait' => $vendorDir . '/symfony/var-dumper/Test/VarDumperTestTrait.php',
    'Symfony\\Component\\VarDumper\\VarDumper' => $vendorDir . '/symfony/var-dumper/VarDumper.php',
    'Symfony\\Polyfill\\Mbstring\\Mbstring' => $vendorDir . '/symfony/polyfill-mbstring/Mbstring.php',
    'Symfony\\Polyfill\\Php80\\Php80' => $vendorDir . '/symfony/polyfill-php80/Php80.php',
    'Symfony\\Polyfill\\Php80\\PhpToken' => $vendorDir . '/symfony/polyfill-php80/PhpToken.php',
    'UnhandledMatchError' => $vendorDir . '/symfony/polyfill-php80/Resources/stubs/UnhandledMatchError.php',
    'ValueError' => $vendorDir . '/symfony/polyfill-php80/Resources/stubs/ValueError.php',
    'Whoops\\Exception\\ErrorException' => $vendorDir . '/filp/whoops/src/Whoops/Exception/ErrorException.php',
    'Whoops\\Exception\\Formatter' => $vendorDir . '/filp/whoops/src/Whoops/Exception/Formatter.php',
    'Whoops\\Exception\\Frame' => $vendorDir . '/filp/whoops/src/Whoops/Exception/Frame.php',
    'Whoops\\Exception\\FrameCollection' => $vendorDir . '/filp/whoops/src/Whoops/Exception/FrameCollection.php',
    'Whoops\\Exception\\Inspector' => $vendorDir . '/filp/whoops/src/Whoops/Exception/Inspector.php',
    'Whoops\\Handler\\CallbackHandler' => $vendorDir . '/filp/whoops/src/Whoops/Handler/CallbackHandler.php',
    'Whoops\\Handler\\Handler' => $vendorDir . '/filp/whoops/src/Whoops/Handler/Handler.php',
    'Whoops\\Handler\\HandlerInterface' => $vendorDir . '/filp/whoops/src/Whoops/Handler/HandlerInterface.php',
    'Whoops\\Handler\\JsonResponseHandler' => $vendorDir . '/filp/whoops/src/Whoops/Handler/JsonResponseHandler.php',
    'Whoops\\Handler\\PlainTextHandler' => $vendorDir . '/filp/whoops/src/Whoops/Handler/PlainTextHandler.php',
    'Whoops\\Handler\\PrettyPageHandler' => $vendorDir . '/filp/whoops/src/Whoops/Handler/PrettyPageHandler.php',
    'Whoops\\Handler\\XmlResponseHandler' => $vendorDir . '/filp/whoops/src/Whoops/Handler/XmlResponseHandler.php',
    'Whoops\\Inspector\\InspectorFactory' => $vendorDir . '/filp/whoops/src/Whoops/Inspector/InspectorFactory.php',
    'Whoops\\Inspector\\InspectorFactoryInterface' => $vendorDir . '/filp/whoops/src/Whoops/Inspector/InspectorFactoryInterface.php',
    'Whoops\\Inspector\\InspectorInterface' => $vendorDir . '/filp/whoops/src/Whoops/Inspector/InspectorInterface.php',
    'Whoops\\Run' => $vendorDir . '/filp/whoops/src/Whoops/Run.php',
    'Whoops\\RunInterface' => $vendorDir . '/filp/whoops/src/Whoops/RunInterface.php',
    'Whoops\\Util\\HtmlDumperOutput' => $vendorDir . '/filp/whoops/src/Whoops/Util/HtmlDumperOutput.php',
    'Whoops\\Util\\Misc' => $vendorDir . '/filp/whoops/src/Whoops/Util/Misc.php',
    'Whoops\\Util\\SystemFacade' => $vendorDir . '/filp/whoops/src/Whoops/Util/SystemFacade.php',
    'Whoops\\Util\\TemplateHelper' => $vendorDir . '/filp/whoops/src/Whoops/Util/TemplateHelper.php',
);
