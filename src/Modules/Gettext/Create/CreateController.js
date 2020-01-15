app.controller('CreateLanguageController', function ($scope, $uibModal) {
    openNewLanguageModal = function() {
        $scope.modal = $uibModal.open({
            animation: true,
            ariaLabelledBy: 'modal-title',
            ariaDescribedBy: 'modal-body',
            templateUrl: 'create-language-modal.html',
            size: 'md',
            scope: $scope
        });
    };

    $scope.newLanguage = function () {
        $scope.language = {};
        openNewLanguageModal();
    };
});
