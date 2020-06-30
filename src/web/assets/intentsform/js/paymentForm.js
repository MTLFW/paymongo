(function(){
   
    document.addEventListener('DOMContentLoaded',function(){
        var form = document.querySelector('#paymentForm');
        form.addEventListener('submit',function(e){
            e.preventDefault();

            var cardNumber = document.querySelector('input[name=number]').value;           
            var cardExpiry = document.querySelector('input[name=expiry]').value;
            var cardCVV = document.querySelector('input[name=cvv]').value;
            var firstName = document.querySelector('input[name=firstName]').value;
            var lastName = document.querySelector('input[name=lastName]').value;
            cardNumber = cardNumber.replace(/\s/g,'');
            cardExpiry = cardExpiry.split("/");
 

            var isValid = validateCardForm(firstName,lastName,cardNumber,cardExpiry,cardCVV);
    
            if(!isValid){
                return false;
            }
    
            var paymentData= {
                data: {
                    attributes: {
                        details: {
                            card_number: cardNumber,
                            exp_month:  parseInt(cardExpiry[0].trim()),
                            exp_year: parseInt(cardExpiry[1].trim()),
                            cvc: cardCVV.trim()
                        },
                        type: 'card',
                    },
                }
            };

            var xhr = new XMLHttpRequest();

            xhr.addEventListener("readystatechange", function () {
                if (this.readyState === this.DONE) { }
            });

            xhr.open("POST", "https://api.paymongo.com/v1/payment_methods");
            xhr.setRequestHeader("Authorization", "Basic " + btoa(document.querySelector(".paymongo-payment-intents-form").getAttribute("data-appkey") + ":"));
            xhr.setRequestHeader("content-type", "application/json");
            xhr.onreadystatechange = (e) => {
                if (xhr.readyState !== 4) {
                return;
                }
                checkoutInstance = JSON.parse(xhr.responseText);
                if (xhr.status === 200) {
                document.querySelector('.card-cvc').classList.remove('has-error-no-border');
                document.querySelector('.card-errors').innerHTML = '';

                let newEl = document.createElement("input");
                newEl.name = "paymentMethodId";
                newEl.value = checkoutInstance.data.id;
                newEl.type = "hidden";

                form.appendChild(newEl);
                form.submit();

                }else{
                    var errorMessages = checkoutInstance.errors;
                    for (var i = 0; i < errorMessages.length; i++) {
                        if(errorMessages[i].source.attribute == 'cvc'){
                            document.querySelector('.card-cvc').classList.add('has-error-no-border');
                        }
                        document.querySelector('.card-errors').innerHTML = errorMessages[i].detail;             
                    }
                }
            };
            xhr.send(JSON.stringify(paymentData));
                    
        });

       

    });

    document.body.addEventListener( 'keyup', function ( event ) { 
        if( event.srcElement.name == 'expiry' ) {            
                addSlashes(document.querySelector('input[name=expiry]'));
                formatString(event);
        };
      } );

})();

function createPaymentIntent(cardData){
    xhr.open("POST", "/actions/commerce-paymongo/payment/create-payment");   
    xhr.setRequestHeader("content-type", "application/json");
    xhr.onreadystatechange = (e) => {
        if (xhr.readyState !== 4) {
        return;
        }

        if (xhr.status === 200) {
        console.log('SUCCESS', xhr.responseText);
        console.log(JSON.parse(xhr.responseText));
        } else {
        console.warn('request_error');
        }
    };
    xhr.send(JSON.stringify(cardData));
}


function initPaymongo() {
   
    let xmlhttp;
    let url = '/actions/commerce-paymongo/payment/create-payment-form?gateway=' +  document.querySelector('input[name=gatewayId]').value;            
    // compatible with IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function(){
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200){            
            document.querySelector('.paymongo-payment-intents-form').innerHTML = xmlhttp.responseText;
            new Card({
                form: document.querySelector('form#paymentForm'),
                container: '.card-wrapper',
                formSelectors: {
                    nameInput: 'input[name="firstName"], input[name="lastName"]',
                },
                messages: {
                    validDate: 'expire\ndate',
                    monthYear: 'mm/yyyy'
                }
            });
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

function validateCardForm(firstName,lastName,cardNumber,cardExpiry,cardCVC){
var error_message;
var isValid = true;
var element = [];
var nameElement = [];
var error_messages = [];
var name_element_error_message = '',card_error_message = '',additional_error_details='';
    
    if(/^$|\s+/.test(firstName)){
        isValid = false;
        nameElement.push("firstname");
        name_element_error_message = "Your "+ nameElement.join(", ") +" should not be empty.";
        document.querySelector('.card-holder-first-name').classList.add('has-error');
    }else{
        document.querySelector('.card-holder-first-name').classList.remove('has-error');
    }

    if(/^$|\s+/.test(lastName)){
        isValid = false;
        nameElement.push("lastname");
        name_element_error_message = "Your "+ nameElement.join(", ") +" should not be empty.";
        document.querySelector('.card-holder-last-name').classList.add('has-error');
    }else{
        document.querySelector('.card-holder-last-name').classList.remove('has-error');
    }
   
    error_messages.push(name_element_error_message);
    
    
    if(!/^\s$|^[0-9X]{16,}$/.test(cardNumber)){
        isValid = false;
        element.push("card number");
        card_error_message = "Your "+ element.join(", ") +" is not valid.";
        document.querySelector('.card-number').classList.add('has-error-no-border');
    }else{
        document.querySelector('.card-number').classList.remove('has-error-no-border');
    }

    var today  = new Date();  
    var diffDate = parseInt(cardExpiry[1]) - parseInt(today.getFullYear())  
    var expiryDate = new Date(cardExpiry[1], (+cardExpiry[0] - 1));
    if((!/^$|^[0-9X]{2}$/.test(cardExpiry[0])) || (!/^$|^[0-9X]{4}$/.test(cardExpiry[1]))){
        isValid = false;
        element.push("card expiry");
        card_error_message = "Your "+ element.join(", ") +" is not valid.";
        additional_error_details = "Sample format for card expiry : MM/YYYY";
        document.querySelector('.card-expiry').classList.add('has-error-no-border');
    }else if(cardExpiry[0] > 12){
        isValid = false;
        element.push("card expiry");
        card_error_message = "Your "+ element.join(", ") +" is not valid.";
        document.querySelector('.card-expiry').classList.add('has-error-no-border');

    }else if(diffDate >= 50){
        isValid = false;
        element.push("card expiry");
        card_error_message = "Your "+ element.join(", ") +" is not valid.";
        additional_error_details = "Year must be at least this year or no later than 50";
        document.querySelector('.card-expiry').classList.add('has-error-no-border');
    }else if(expiryDate < today) {
        isValid = false;
        element.push("card expiry");
        card_error_message = "Your "+ element.join(", ") +" is not valid.";
        additional_error_details = "Expiry date must not be earlier than the current date";
        document.querySelector('.card-expiry').classList.add('has-error-no-border');
    }else{
        document.querySelector('.card-expiry').classList.remove('has-error-no-border');      
    }
   

    if(!/^\s$|^[0-9X]{3,4}$/.test(cardCVC)){
        isValid = false;
        element.push("card cvc");
        card_error_message = "Your "+ element.join(", ") +" is not valid.";
        additional_error_details = "CVV must be 3-4 digits only.";
        document.querySelector('.card-cvc').classList.add('has-error-no-border');
    }else{
        document.querySelector('.card-cvc').classList.remove('has-error-no-border'); 
    }
    
    error_messages.push(card_error_message);
      
    document.querySelector('.card-errors').innerHTML = error_messages.join('') + " " + additional_error_details;

    //Check fields to 
    if(isValid){
        document.querySelector('.card-number').classList.remove('has-error');
        document.querySelector('.card-number').classList.remove('has-error-no-border');
        document.querySelector('.card-errors').innerHTML = "";
    }
    return isValid;

}


function addSlashes (element) {	
    let ele = element.value;     
    ele = ele.split('/').join('');    // Remove slash (/) if mistakenly entered.
    if(ele.length < 4 && ele.length > 0){
        let finalVal = ele.match(/.{1,2}/g).join('/');
        element.value = finalVal;
    }
}

function formatString(e) {
    var inputChar = String.fromCharCode(event.keyCode);
    var code = event.keyCode;
    var allowedKeys = [8];
    if (allowedKeys.indexOf(code) !== -1) {
      return;
    }
  
    event.target.value = event.target.value.replace(
      /^([1-9]\/|[2-9])$/g, '0$1/' // 3 > 03/
    ).replace(
      /^(0[1-9]|1[0-2])$/g, '$1/' // 11 > 11/
    ).replace(
      /^1([3-9])$/g, '01/$1' // 13 > 01/3 
    ).replace(
      /^0\/|0+$/g, '0' // 0/ > 0 and 00 > 0
    ).replace(
      /[^\d|^\/]*/g, '' // To allow only digits and `/` 
    ).replace(
      /\/\//g, '/' // Prevent entering more than 1 `/`
    );
  }

initPaymongo();
