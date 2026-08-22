import { FiRotateCcw } from "react-icons/fi";

export function Return() {
    return (
        <div className="space-y-8">
          <div className="text-center">
            <h1 className="text-4xl font-bold text-gray-900 mb-4">Returns & Exchanges</h1>
            <p className="text-gray-600 text-lg">We want you to love your purchase. Here's our hassle-free return policy.</p>
          </div>
          
          <div className="bg-green-50 border border-green-200 rounded-lg p-6">
            <h2 className="text-xl font-semibold text-green-900 mb-2">30-Day Return Guarantee</h2>
            <p className="text-green-800">
              Not satisfied with your purchase? Return it within 30 days for a full refund or exchange. 
              No questions asked!
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-8">
            <div className="bg-blue-50 rounded-xl p-6">
              <div className="flex items-center mb-4">
                <div className="bg-blue-100 p-3 rounded-full mr-4">
                  <FiRotateCcw className="w-6 h-6 text-blue-600" />
                </div>
                <h3 className="text-xl font-semibold text-gray-900">Easy Returns Process</h3>
              </div>
              <ol className="space-y-3 text-gray-700">
                <li className="flex items-start">
                  <span className="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-semibold mr-3 mt-0.5">1</span>
                  <span>Log into your account and go to "My Orders"</span>
                </li>
                <li className="flex items-start">
                  <span className="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-semibold mr-3 mt-0.5">2</span>
                  <span>Click "Return Item" and select your reason</span>
                </li>
                <li className="flex items-start">
                  <span className="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-semibold mr-3 mt-0.5">3</span>
                  <span>Print your prepaid return label</span>
                </li>
                <li className="flex items-start">
                  <span className="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-semibold mr-3 mt-0.5">4</span>
                  <span>Pack and ship your item back to us</span>
                </li>
              </ol>
            </div>

            <div className="bg-purple-50 rounded-xl p-6">
              <h3 className="text-xl font-semibold text-gray-900 mb-4">Return Timeline</h3>
              <div className="space-y-4">
                <div className="flex items-center">
                  <div className="bg-purple-100 text-purple-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold mr-3">1</div>
                  <div>
                    <div className="font-semibold text-gray-900">Within 30 days</div>
                    <div className="text-gray-600 text-sm">Initiate your return</div>
                  </div>
                </div>
                <div className="flex items-center">
                  <div className="bg-purple-100 text-purple-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold mr-3">2</div>
                  <div>
                    <div className="font-semibold text-gray-900">5-7 business days</div>
                    <div className="text-gray-600 text-sm">Shipping time to our warehouse</div>
                  </div>
                </div>
                <div className="flex items-center">
                  <div className="bg-purple-100 text-purple-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold mr-3">3</div>
                  <div>
                    <div className="font-semibold text-gray-900">3-5 business days</div>
                    <div className="text-gray-600 text-sm">Processing and refund issued</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="prose prose-lg max-w-none">
            <section className="mb-8">
              <h2 className="text-2xl font-semibold text-gray-900 mb-4">Return Policy Details</h2>
              <div className="bg-gray-50 rounded-lg p-6 mb-6">
                <h3 className="font-semibold text-gray-900 mb-3">Items Eligible for Return:</h3>
                <ul className="list-disc list-inside text-gray-700 space-y-1">
                  <li>Items in original condition with tags attached</li>
                  <li>Unworn, unwashed, and undamaged items</li>
                  <li>Items returned within 30 days of purchase</li>
                  <li>Items purchased at full price or on sale</li>
                </ul>
              </div>

              <div className="bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 className="font-semibold text-red-900 mb-3">Items NOT Eligible for Return:</h3>
                <ul className="list-disc list-inside text-red-800 space-y-1">
                  <li>Personalized or customized items</li>
                  <li>Underwear, swimwear, and intimate apparel</li>
                  <li>Items damaged by normal wear and tear</li>
                  <li>Items returned after 30 days</li>
                  <li>Gift cards and digital products</li>
                </ul>
              </div>
            </section>

            <section className="mb-8">
              <h2 className="text-2xl font-semibold text-gray-900 mb-4">Exchanges</h2>
              <p className="text-gray-700 leading-relaxed mb-4">
                Need a different size or color? We offer free exchanges for items of equal or lesser value. 
                If you're exchanging for a higher-priced item, you'll only pay the difference.
              </p>
              <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p className="text-yellow-800 font-semibold">
                  Pro Tip: Exchanges are processed faster than returns! Your new item ships as soon as we receive your return.
                </p>
              </div>
            </section>

            <section className="mb-8">
              <h2 className="text-2xl font-semibold text-gray-900 mb-4">Refund Information</h2>
              <p className="text-gray-700 leading-relaxed mb-4">
                Refunds are processed to your original payment method within 3-5 business days after we receive your return.
              </p>
              <ul className="list-disc list-inside text-gray-700 space-y-2 ml-4">
                <li>Credit card refunds: 3-5 business days</li>
                <li>PayPal refunds: 1-2 business days</li>
                <li>Store credit: Instant (optional for faster processing)</li>
                <li>Original shipping costs are non-refundable</li>
              </ul>
            </section>

            <section className="mb-8">
              <h2 className="text-2xl font-semibold text-gray-900 mb-4">International Returns</h2>
              <p className="text-gray-700 leading-relaxed">
                International customers are responsible for return shipping costs. We recommend using a trackable shipping method. 
                Duties and taxes are non-refundable.
              </p>
            </section>

            <section className="mb-8">
              <h2 className="text-2xl font-semibold text-gray-900 mb-4">Damaged or Defective Items</h2>
              <p className="text-gray-700 leading-relaxed mb-4">
                Received a damaged or defective item? We'll make it right immediately:
              </p>
              <ul className="list-disc list-inside text-gray-700 space-y-2 ml-4">
                <li>Contact us within 7 days of receipt</li>
                <li>Provide photos of the damage or defect</li>
                <li>We'll send a replacement or full refund</li>
                <li>No need to return the damaged item</li>
              </ul>
            </section>

            <section className="mb-8">
              <h2 className="text-2xl font-semibold text-gray-900 mb-4">Need Help?</h2>
              <p className="text-gray-700 leading-relaxed mb-4">
                Our customer service team is here to help with any questions about returns or exchanges.
              </p>
              <div className="bg-gray-50 rounded-lg p-4">
                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-semibold text-gray-900 mb-2">Contact Information:</h4>
                    <p className="text-gray-700">Email: returns@company.com</p>
                    <p className="text-gray-700">Phone: 1-800-RETURNS</p>
                    <p className="text-gray-700">Hours: Mon-Fri 9AM-6PM EST</p>
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-900 mb-2">Return Address:</h4>
                    <p className="text-gray-700">
                      Returns Department<br />
                      123 Commerce Street<br />
                      Warehouse City, ST 12345
                    </p>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <div className="text-center bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-8">
            <h2 className="text-2xl font-semibold text-gray-900 mb-4">Still Have Questions?</h2>
            <p className="text-gray-600 mb-6">
              Our customer service team is standing by to help make your return as smooth as possible.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <button className="bg-yellow hover:bg-yellow-600 cursor-pointer text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                Start Return Process
              </button>
              <button className="border border-gray-300 hover:border-gray-400 cursor-pointer text-gray-700 font-semibold py-3 px-6 rounded-lg transition-colors">
                Contact Support
              </button>
            </div>
          </div>
        </div>
      );
}