var cardNumber = document.getElementById('field-cardNumber');

console.log(cardNumber);
payform.cardNumberInput(cardNumber);
cardNumber.addEventListener('change', e => {
    e.target.style.borderColor = payform.validateCardNumber(e.target.value) ? '#B0B0B0' : 'red';
});

document.getElementById('field-cardCVV').addEventListener('change', e => {
    e.target.style.borderColor = payform.validateCardCVC(e.target.value) ? '#B0B0B0' : 'red';
});

var cardExpiryMonthElement = document.getElementById('field-cardExpiryMonth');
var cardExpiryYearElement = document.getElementById('field-cardExpiryYear');

var listener = e => {
    let isValid = payform.validateCardExpiry(cardExpiryMonthElement.value, '20'+cardExpiryYearElement.value);

    cardExpiryMonthElement.style.borderColor =  isValid ? '#B0B0B0' : 'red';
    cardExpiryYearElement.style.borderColor = isValid ? '#B0B0B0' : 'red';
};

cardExpiryMonthElement.addEventListener('change', listener);
cardExpiryYearElement.addEventListener('change', listener);