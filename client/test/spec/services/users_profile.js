'use strict';

describe('Service: usersProfile', function () {

  // load the service's module
  beforeEach(module('tixmall'));

  // instantiate service
  var usersProfile;
  beforeEach(inject(function (_usersProfile_) {
    usersProfile = _usersProfile_;
  }));

  it('should do something', function () {
    expect(!!usersProfile).toBe(true);
  });

});
