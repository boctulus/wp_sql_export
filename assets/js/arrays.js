
// ver https://lodash.com/docs/4.17.15#drop

function array_keys(arr) {
    tempObj = [];
    
    Object.keys(arr).forEach((key, prop) => {
        if (arr[key]) { 
            tempObj.push(key) 
        }
    });

    return tempObj;
}

function array_remove(arr, value){
    for( var i = 0; i < arr.length; i++){     
        if ( arr[i] === value) {     
            arr.splice(i, 1); 
        }    
    }

    return arr;
}
