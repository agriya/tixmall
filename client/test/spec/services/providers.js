'use strict';

describe('Service: providers', function () {

  // load the service's module
  beforeEach(module('tixmall'));

  // instantiate service
  var providers;
  beforeEach(inject(function (_providers_) {
    providers = _providers_;
  }));

  it('should do something', function () {
    expect(!!providers).toBe(true);
  });

});
