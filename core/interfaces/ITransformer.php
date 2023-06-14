<?php

namespace boctulus\SW\core\interfaces;

use boctulus\SW\controllers\Controller;

interface ITransformer {
    function transform(object $user, Controller $controller = NULL);
}