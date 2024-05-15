document.addEventListener('DOMContentLoaded', () => {
    SequraFE.utilities.showLoader();
    SequraFE.state = new SequraFE.StateController(SequraFE._state_controller);
    SequraFE.state.display();
    SequraFE.utilities.hideLoader();
});