const defaultShopperData = {
    address1: "Carrer d'Alí Bei, 7",
    email: 'test@sequra.es',
    city: 'Barcelona',
    state: 'Barcelona',
    postcode: '08010',
    country: 'Spain',
    phone: '666666666',
    dateOfBirth: '01/01/2000',
    dni: '23232323T',
    creditCard: {
        number: '4716773077339777',
        exp: '12/30',
        cvc: '123',
    },
    otp: ['6', '6', '6', '6', '6']
}

export const shopper = {
    approve: {
        firstName: 'Review Test Approve',
        lastName: 'Review Test Approve',
        ...defaultShopperData

    },
    cancel: {
        firstName: 'Review Test Cancel',
        lastName: 'Review Test Cancel',
        ...defaultShopperData

    },
    nonSpecial: {
        firstName: 'Fulano',
        lastName: 'De Tal',
        ...defaultShopperData
    }
}

export const sqProduct = {
    fp1: {
        es: {
            name: 'Paga con tarjeta',
        }
    },
    i1: {
        es: {
            name: 'Recibe tu compra antes de pagar',
        }
    },
    sp1: {
        es: {
            name: 'Divide en 3 partes de 33,33 €/mes ¡Gratis!',
        }
    },
    pp3: {
        es: {
            name: 'Paga Fraccionado',
        }
    },
    pp3Decombined: {
        es: {
            name: "€/mes (DECOMBINED)",
        }
    }
}

export const merchant = {
    dummy: {
        username: 'dummy_automated_tests',
        password: process.env.DUMMY_PASSWORD,
        assetsKey: process.env.DUMMY_ASSETS_KEY,
        ref: {
            ES: 'dummy_automated_tests',
            FR: 'dummy_automated_tests_fr',
            IT: 'dummy_automated_tests_it',
            PT: 'dummy_automated_tests_pt',
            CO: 'dummy_automated_tests_co',
            PE: 'dummy_automated_tests_pe',
        },
        paymentMethods: {
            ES: [
                'Paga Después',
                'Divide tu pago en 3',
                'Paga Fraccionado'
            ],
            FR: [
                'Payez en plusieurs fois'
            ],
            PT: [
                'Pagamento Fracionado'
            ],
            IT: [
                'Pagamento a rate'
            ],
            CO: [],
            PE: []
        }
    },
    dummyServices: {
        username: 'dummy_services_automated_tests',
        password: process.env.DUMMY_SERVICE_PASSWORD,
    }
}

export const countries = {
    default: {
        ES: 'Spain',
        FR: 'France',
        IT: 'Italy',
        PT: 'Portugal',
        CO: 'Colombia',
        PE: 'Peru',
    }
}