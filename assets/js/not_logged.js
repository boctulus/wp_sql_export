

/*
    Reemplaza boton de ver carrito en el navbar por boton "ir a cotizacion"
*/

const quoter_url  = '/cotizador/'
const new_button  = `<li class="account-item has-icon has-dropdown"><a href="${quoter_url}" class="account-link"><span class="header-account-title go_quote_btn">IR A COTIZACIÓN</span></a></li>`

if (!is_admin_page()){
    jQuery('.header-cart-link').parent().replaceWith(new_button)
}
