import { decodeEntities } from '@wordpress/html-entities';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry
const { getSetting } = window.wc.wcSettings
const paymentId = 'subscription_wompi_sw'

const settings = getSetting( `${paymentId}_data`, {} )

const label = decodeEntities( settings.title )

const Content = () => {
    return decodeEntities( settings.description || '' )
}

const Icon = () => {
    return settings.icon
        ? <img src={settings.icon} style={{ float: 'right', marginRight: '20px' }}  alt={label}/>
        : ''
}

const Label = () => {
    return (
        <span style={{ width: '100%' }}>
            {label}
            <Icon />
        </span>
    )
}
console.log(settings)

registerPaymentMethod( {
    name: paymentId,
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    }
} )