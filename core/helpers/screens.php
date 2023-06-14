<?php

use boctulus\SW\core\libs\Strings;
use boctulus\SW\core\libs\Url;

/*
    Deberia mergearse con la lib Page
*/

function is_dashboard_user_page(){
    $slugs   = trim(rtrim(Url::getSlugs(null, true), '/'));
    return ($slugs == '/my-account/edit-account' || $slugs == '/dashboard/editar-cuenta');
}

function is_dashboard_prod_page()
{
    $slugs = trim(rtrim(Url::getSlugs(null, true), '/'));

    if (Strings::contains('/post.php', $slugs)){
        return true;
    }

    if (Strings::contains('/edit.php', $slugs)){
        $q = Url::queryString();

        if (isset($q['post_type']) && $q['post_type'] == 'product'){
            return true;
        }
    }

    return false;
}

/*
    Si se le pasa el step ("init", "mapping", "import" y "done") comprueba este en ese step

    step === false o "init", significa paso inicial

    Ej de uso:

    if (is_dashboard_prod_importer_page("init")){
        view('upload_csv.php');
    }
*/
function is_dashboard_prod_importer_page($step = null)
{
    if (!is_dashboard_prod_page()){
        return false;
    }

    $q = Url::queryString();

    if (isset($q['page']) && $q['page'] == 'product_importer'){
        if ($step !== null){
            if ($step == "init" || $step === false){
                return !isset($q['step']);
            }

            return (isset($q['step']) && $q['step'] == $step);
        }

        return true;
    }

    return false;
}