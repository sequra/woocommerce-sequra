import './core/ImagesProvider';
import './core/AjaxService';
import './core/TranslationService';
import './core/TemplateService';
import './core/UtilityService';
import './core/ValidationService';
import './core/ResponseService';
import './core/PageControllerFactory';
import './core/FormFactory';
import './core/StateUUIDService';
import './core/ElementGenerator';
import './core/ModalComponent';
import './core/DropdownComponent';
import './core/MultiItemSelectorComponent';
import './core/DataTableComponent';
import './core/PageHeaderComponent';
import './core/ConnectionSettingsForm';
import './core/WidgetSettingsForm';
import './core/GeneralSettingsForm';
import './core/OrderStatusMappingSettingsForm';
import './core/StateController';
import './core/OnboardingController';
import './core/PaymentController';
import './core/SettingsController';
import './core/AdvancedController';

document.addEventListener('DOMContentLoaded', () => {
    SequraFE.utilities.showLoader();
    SequraFE.state = new SequraFE.StateController(SequraFE._state_controller);
    SequraFE.state.display();
    SequraFE.utilities.hideLoader();
});