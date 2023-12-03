function showCustomerTour(first_name){
     console.log("showCustomerTour() ");
    //console.log("showCustomerTour() => "+first_name);
     //const first_name = 'Hello ';
    const tour = new Shepherd.Tour({
  defaultStepOptions: {
    cancelIcon: {
      enabled: true
    },
    classes: 'class-1 class-2',
    scrollTo: { behavior: 'smooth', block: 'center' }
  }
});

tour.addStep({
  title: 'Welcome '+first_name,
  text: 'Choose your favourite e-books, magazines, audiobooks and read or listen them online from your computer, mobilephone or tablet.',
  attachTo: {
    element: '.custTour4',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
        return this.back();
      },
      classes: 'shepherd-button-secondary',
      text: 'Back'
    },
    {
      action() {
        return this.next();
      },
      text: 'Next'
    }
  ],
  id: 'custTour4'
});


tour.addStep({
  title: 'Save Your Reads',
  text: 'Hi '+first_name+'! You can save your reads before you exit to start from the same page you left.',
  attachTo: {
    element: '.custTour1',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
        return this.next();
      },
      text: 'Next'
    }
  ],
  id: 'custTour1'
});

tour.addStep({
  title: 'REVIEW MAGAZINES AND E-BOOKS',
  text: 'True reviews can help other readers make their choice. Be descriptive in your reviews. ',
  attachTo: {
    element: '.custTour2',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
        return this.back();
      },
      classes: 'shepherd-button-secondary',
      text: 'Back'
    },
    {
      action() {
        return this.next();
      },
      text: 'Next'
    }
  ],
  id: 'custTour2'
});

tour.addStep({
  title: 'Access to Favourite List',
  text: 'Add magazines and e-books to your wishlist to find them in your favourite list.',
  attachTo: {
    element: '.custTour3',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
        return this.back();
      },
      classes: 'shepherd-button-secondary',
      text: 'Back'
    },
    {
      action() {
        return this.next();
      },
      text: 'Next'
    }
  ],
  id: 'custTour3'
});


tour.addStep({
  title: 'Earn Reward Points',
  text: 'Earn reward points for your activities on Bazichic that you can redeem as an offer or directly into your bank account via Paypal.',
  attachTo: {
    element: '.custTour4',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
        return this.back();
      },
      classes: 'shepherd-button-secondary',
      text: 'Back'
    },
    {
      action() {
        return this.next();
      },
      text: 'Finish'
    }
  ],
  id: 'custTour4'
});

tour.start();
}

function showAffiliateTour(first_name){
    //alert("showAffiliateTour");
    //const first_name = 'Hello ';
    const tour = new Shepherd.Tour({
  defaultStepOptions: {
    cancelIcon: {
      enabled: true
    },
    classes: 'class-1 class-2',
    scrollTo: { behavior: 'smooth', block: 'center' }
  }
});

tour.addStep({
  title: 'LET US START',
  text: 'Hi '+first_name+'! First generate your referral code that you can share with anyone. You can optionally generate multiple referral codes to track your conversions based on an audience or event.',
  attachTo: {
    element: '.firstTour',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
        return this.next();
      },
      text: 'Next'
    }
  ],
  id: 'creating'
});

tour.addStep({
  title: 'MAKE CONNECTIONS',
  text: 'Earn affiliate benefits when your connections grow. Update your paypal account email in profile page to redeem your earnings.',
  attachTo: {
    element: '.secondTour',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
        return this.back();
      },
      classes: 'shepherd-button-secondary',
      text: 'Back'
    },
    {
      action() {
        return this.finish();
      },
      text: 'Finish'
    }
  ],
  id: 'creating2'
});


tour.start();
console.log('tour started'); 
}