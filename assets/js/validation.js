/*
    Bootstrap validation
*/

const setFormValidations = (validations) => {
    for (let field in validations) {

        const validation = validations[field].shift();
        const { error, error_detail } = validation;
        const field_selector = '#col-'+field;
        const feedback_selector = '#invalid-col-'+field;

        $(field_selector).removeClass('is-valid, is-invalid')

        if (error == false){
            $(field_selector).addClass('is-valid')
            $(feedback_selector).text('')
        } else {
            $(field_selector).addClass('is-invalid')
            $(feedback_selector).text(error_detail)
        }
    }
}

const clearFormValidations = () => {
    $('input').removeClass('is-valid')
    $('input').removeClass('is-invalid')
    $('.invalid-feedback').text('')
}

const clearForm = (formId) => {
    $(`#${formId}`).trigger('reset')
    clearFormValidations()
}