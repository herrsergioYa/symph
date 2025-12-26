BX.ready(async function () {
    debugger;
    //let result = await BX.ajax.runAction('local:lib.api.test.example', {
    //let result = await BX.ajax.runComponentAction('gsv:ajax.class', 'doSomething', {
    let result = await BX.ajax.runComponentAction('gsv:ajax.class', 'sayBye', {
        json: {
            param1: 123,
            param2: 456,
        },
        mode: 'ajax',
    });
    console.log(result);
})