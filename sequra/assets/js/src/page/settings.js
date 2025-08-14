import '../../../integration-core-ui/js/ImagesProvider';
import '../../../integration-core-ui/js/AjaxService';
import '../../../integration-core-ui/js/TranslationService';
import '../../../integration-core-ui/js/TemplateService';
import '../../../integration-core-ui/js/UtilityService';
import '../../../integration-core-ui/js/ValidationService';
import '../../../integration-core-ui/js/ResponseService';
import '../../../integration-core-ui/js/PageControllerFactory';
import '../../../integration-core-ui/js/FormFactory';
import '../../../integration-core-ui/js/StateUUIDService';
import '../../../integration-core-ui/js/ElementGenerator';
import '../../../integration-core-ui/js/ModalComponent';
import '../../../integration-core-ui/js/DropdownComponent';
import '../../../integration-core-ui/js/MultiItemSelectorComponent';
import '../../../integration-core-ui/js/DataTableComponent';
import '../../../integration-core-ui/js/PageHeaderComponent';
import '../../../integration-core-ui/js/ConnectionSettingsForm';
import '../../../integration-core-ui/js/WidgetSettingsForm';
import '../../../integration-core-ui/js/GeneralSettingsForm';
import '../../../integration-core-ui/js/OrderStatusMappingSettingsForm';
import '../../../integration-core-ui/js/StateController';
import '../../../integration-core-ui/js/OnboardingController';
import '../../../integration-core-ui/js/PaymentController';
import '../../../integration-core-ui/js/SettingsController';
import '../core/AdvancedController'; // TODO: Migrate this to integration-core-ui

document.addEventListener('DOMContentLoaded', () => {
    SequraFE.utilities.showLoader();
    SequraFE.state = new SequraFE.StateController(SequraFE._state_controller);
    SequraFE.state.display();
    SequraFE.utilities.hideLoader();
});