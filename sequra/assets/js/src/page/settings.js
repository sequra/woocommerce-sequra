
import 'sequra-core-admin-fe/dist/resources/js/RepeaterFieldsComponent';
import 'sequra-core-admin-fe/dist/resources/js/ImagesProvider';
import 'sequra-core-admin-fe/dist/resources/js/AjaxService';
import 'sequra-core-admin-fe/dist/resources/js/TranslationService';
import 'sequra-core-admin-fe/dist/resources/js/TemplateService';
import 'sequra-core-admin-fe/dist/resources/js/UtilityService';
import 'sequra-core-admin-fe/dist/resources/js/ValidationService';
import 'sequra-core-admin-fe/dist/resources/js/ResponseService';
import 'sequra-core-admin-fe/dist/resources/js/PageControllerFactory';
import 'sequra-core-admin-fe/dist/resources/js/FormFactory';
import 'sequra-core-admin-fe/dist/resources/js/StateUUIDService';
import 'sequra-core-admin-fe/dist/resources/js/ElementGenerator';
import 'sequra-core-admin-fe/dist/resources/js/ModalComponent';
import 'sequra-core-admin-fe/dist/resources/js/DropdownComponent';
import 'sequra-core-admin-fe/dist/resources/js/MultiItemSelectorComponent';
import 'sequra-core-admin-fe/dist/resources/js/DataTableComponent';
import 'sequra-core-admin-fe/dist/resources/js/PageHeaderComponent';
import 'sequra-core-admin-fe/dist/resources/js/ConnectionSettingsForm';
import 'sequra-core-admin-fe/dist/resources/js/DeploymentsSettingsForm';
import 'sequra-core-admin-fe/dist/resources/js/WidgetSettingsForm';
import 'sequra-core-admin-fe/dist/resources/js/GeneralSettingsForm';
import 'sequra-core-admin-fe/dist/resources/js/OrderStatusMappingSettingsForm';
import 'sequra-core-admin-fe/dist/resources/js/StateController';
import 'sequra-core-admin-fe/dist/resources/js/OnboardingController';
import 'sequra-core-admin-fe/dist/resources/js/PaymentController';
import 'sequra-core-admin-fe/dist/resources/js/SettingsController';
import 'sequra-core-admin-fe/dist/resources/js/DeploymentsModalForm';
import '../core/AdvancedController'; // TODO: Migrate this to integration-core-ui

document.addEventListener('DOMContentLoaded', () => {
    SequraFE.utilities.showLoader();
    SequraFE.state = new SequraFE.StateController(SequraFE._state_controller);
    SequraFE.state.display();
    SequraFE.utilities.hideLoader();
});