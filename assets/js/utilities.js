/*
    @author Pablo Bozzolo
*/

if (typeof $ == 'undefined' && typeof jQuery != 'undefined'){
    $=jQuery
}

const ucfirst = s => (s && s[0].toUpperCase() + s.slice(1)) || ""

/*
    https://stackoverflow.com/questions/27746304/how-to-check-if-an-object-is-a-promise/27746324#27746324
*/

const isPromise = p => {
    return p && Object.prototype.toString.call(p) === "[object Promise]";
}

// General
function setDropdownOptions(select_elem, options, default_option){
    select_elem.innerHTML = '';

    let opt = new Option(default_option['text'], default_option['value']);
    opt.setAttribute('selected', true);
    select_elem.appendChild(opt);

    if (typeof options == 'undefined' || options == null || options.length == 0){
        select_elem.disabled = true;
        return;
    } else {
        select_elem.disabled = false;
    }

    for (let i=0; i<options.length; i++){
        let opt = new Option(options[i]['text'], options[i]['value']);
        select_elem.appendChild(opt);
    }
}

/*
    Antes llamada decodeProp

    Trabaja con var_encode() de PHP
*/
const var_decode = (prop_id) => {
    const el = document.getElementById(prop_id + '-encoded');

    if (el == null){
        throw `Propery ${prop_id} not found`
    }

    const val = el.value;

    if (val == null){
        throw `Value of ${prop_id} is empty?`
    }

    const bin = atob(val);

    if (bin.startsWith('--array--')){
        return JSON.parse(bin.substring(9));
    }

    return bin;
}

/*
    Los elementos del formulario seran recogidos en un objeto

    Si use_id es true, se usa su id y si es false, el name

    De especificarse un prefijo, puede eliminarlo de cada nombre de campo

    Ej:

        getFormData($("#quoter_contact_form"), false)

    o con prefijo:

        getFormData($("#quoter_contact_form"), false, 'col-')
*/
const getFormData = (formElem, use_id = true, prefix = null) => {
    const serialized = jQuery(formElem).serializeArray();
    const jsonData = {};

    serialized.forEach((item) => {
        let field  = use_id ? item.id : item.name;

        if (prefix != null && field.startsWith(prefix)){
            field = field.substr(prefix.length);
        }

        jsonData[field] = item.value
        
    });

    return jsonData;
}

/*
    Los elementos del formulario poseen cierta clase de css,
    seran recogidos en el objeto

    Ej:

        getObjFromElems('col2save')

    De especificarse un prefijo, puede eliminarlo de cada nombre de campo

    Ej:

        getObjFromElems('col2save', 'col-')

    Si use_name es true, buscara los elementos por name y no por id
*/
const getFormDataByClassName = (elem_class, use_id = true, prefix = null) => {
    let obj = {};

    $('.'+elem_class).each((ix, el) => {
        let field  = use_id ? el.id : el.name;

        if (prefix != null && field.startsWith(prefix)){
            field = field.substr(prefix.length);
        }

        obj[field] = el.value;
    })

    return obj;
}


/*
    Rellena un formulario 

    Ej:

        fillForm(obj, 'col-')

    Funciona tambien usando como datasource un storage

    Ej:

        fillForm(fromStorage().form.contact)
*/
const fillForm = (data_obj, prefix = null) => {
    if (typeof data_obj !== 'object'){
        return;
    }

    for (const [key, value] of Object.entries(data_obj)) {
        jQuery('#' + (prefix == null ? '' : prefix) + key).val(value)
    }
}

