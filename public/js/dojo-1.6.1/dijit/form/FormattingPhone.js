declare('calpoly/form/FormattingPhoneNumber', [ValidationTextBox], {

    regExp:"\\d{3}-\\d{3}-\\d{4}",
    invalidMessage:"Please enter a 10-digit phone number",

    filter:function(val){
        val = lang.trim(val);
        var s = [];
        for(var i=0; i<val.length;i++){
            if(!isNaN(val[i])){
                s.push(val[i]);
            }
        }
        if(s.length!=10){
            // Not a 10-digit phone number, return what the user typed, regex will invalidate.
            return val;
        }
        s.splice(3,0,'-');
        s.splice(7,0,'-');
        return s.join('');
    }
});