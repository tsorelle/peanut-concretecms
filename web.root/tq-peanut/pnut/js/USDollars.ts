/**
 * Created by Terry on 2/16/2016.
 */
/**
 * Created by Terry on 2/19/2015.
 */
namespace Peanut {
    export class USDollars {

        /**
         * Returns number formated for USD
         *
         * assumes previous rounding of value. If not rounded use USDollars.format()
         *
         * @param value
         * @param defaultResult
         * @returns {string}
         */
        public static toUSD(value:any, defaultResult = '') {
            if (isNaN(value) || value < 0.005) {
                return defaultResult;
            }
            let s = value.toLocaleString();  // adds comma seperators
            let dec = '00';
            let parts = s.split('.');
            if (parts.length > 1) {
                switch (parts[1].length) {
                    case 0 :
                        break;
                    case 1 :
                        dec = parts[1] + '0';
                        break;
                    case 2:
                        dec = parts[1];
                        break;
                }
            }
            return '$' + parts[0] + '.' + dec;
        }

        /**
         * Rounds number and returns formated for USD
         *
         * @param value
         * @param defaultResult
         * @returns {string}
         */
        public static format(value:any, defaultResult = '') {
            value = USDollars.toNumber(value);
            return USDollars.toUSD(value,defaultResult);
        }

        /**
         * Round number to two decimal places
         *
         * @param n
         * @returns {number | null}
         */
        public static roundNumber(n:any) : any {
            if (n === 0) {
                return 0;
            }
            if (n) {
                let parts = n.toString().split('.');
                let dollars = Number(parts[0]);
                let cents = 0;
                let extra = 0;
                if (parts.length > 1) {
                    let c = parts[1].substr(0,2);
                    cents = Number(c);
                    if (c.length == 1) {
                        cents = cents * 10;
                    }
                    extra = parts[1].substring(2, 3);
                }
                if (extra) {
                    if (Number(extra) > 5) {
                        cents += 1;
                    }
                    if (cents > 99) {
                        dollars += 1;
                        cents = 0;
                    }
                }
                let zeroPad = cents < 10 ? '0' : '';
                return Number(dollars + '.' + zeroPad + cents);
            }
            else {
                return null;
            }
        }

        public static toNumber(n:any) : any {
            let t = typeof n;
            switch (t) {
                case 'number' :
                    break;
                case 'string' :
                    n = n.replace(/,/g, '').replace('$', '').trim();
                    if (isNaN(n)) {
                        return null;
                    }
                    break;
                default:
                    return null;
            }
            return USDollars.roundNumber(n);
        }

        /**
         *
         * @param n  (should be rounded with USDollars.toNumber())
         * @returns {string}
         */
        public static balanceMessage(n : any) : string {
            if (!n) {
                return (n === 0) ? 'Paid in full' : '';
            }
            if (n < 0) {
                return 'Refund due ' + USDollars.toUSD(Math.abs(Number(n)));
            }
            if (n < 0.009) {
                return 'Balance paid in full';
            }
            return 'Balance due '  + USDollars.toUSD(n);
        }
    }

}