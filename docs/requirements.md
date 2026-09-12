# Laravel + Vue Developer Exercise — Mini Farm Manager

> Source: `laravel-vue-developer-exercise-farm-manager.pdf` (same folder)

## Time expectation

Please spend no more than 3-4 hours on this exercise. We prefer a smaller, well-considered implementation over a large unfinished one.

## Purpose

Build a small farm management application using Laravel and Vue. This is not expected to be production-ready. We are interested in how you structure an application, model a business problem, make technical decisions, and communicate your thinking.

You may use AI tools such as ChatGPT, Claude, GitHub Copilot, or similar. We use AI-assisted development ourselves and are interested in how you direct, verify, and improve the output of these tools.

## Scenario

A farmer wants a simple system for keeping track of the animals on their farm. Animals are kept in paddocks and may be moved between paddocks over time. The farmer also wants to keep a simple health history for each animal.

## Core requirements

### Animals

The user should be able to view animals, add an animal, edit an animal, and view an individual animal.

- Tag number
- Name, if applicable
- Species
- Sex
- Date of birth
- Any other fields you believe are useful

### Paddocks

The user should be able to view paddocks, add or edit a paddock, and see which animals are currently in each paddock.

- Name
- Maximum capacity

### Animal movements

An animal can be moved from one paddock to another. The application should:

- Record each movement
- Keep the animal's previous movement history
- Show which paddock the animal is currently in
- Prevent an animal from being moved into a paddock that is already at capacity

### Health records

The user should be able to record a health event against an animal. The animal page should display its health history.

- Date
- Type or description
- Notes

### Dashboard

Create a simple dashboard showing information you believe would be useful to the farmer. Examples could include:

- Number of animals
- Animals by species
- Animals currently in each paddock
- Recent animal movements

### Additional feature of your choosing

Choose and implement one additional feature.

Add one feature that is not specified above. Choose something you believe would make the application more useful, reliable, or pleasant to use. Keep it proportionate to the time limit. In your README, briefly explain why you chose it, the assumptions you made, and any trade-offs.

## Technical requirements

- Use Laravel for the backend and Vue for the frontend
- Include database migrations
- Apply appropriate backend validation
- Provide a reasonable frontend user experience
- Include automated tests for behaviour you consider important
- Authentication is not required

## What we are looking for

- Domain modelling and database design
- Laravel and Vue knowledge
- Code organisation, naming, and readability
- Validation and handling of edge cases
- User experience and sensible error handling
- Testing decisions
- Appropriate use of abstractions
- Pragmatic engineering judgement
- Clear reasoning behind your chosen additional feature

## Submission

Submit your code in a Git repository together with a short README covering:

- How to run the application
- Assumptions you made
- Important technical decisions
- Anything you deliberately left out
- What you would improve before production
- Your additional feature and why you chose it
- How you used AI tools, if applicable

## A final note

There are not necessarily right or wrong answers. We are interested in the reasoning behind your decisions and your ability to deliver a coherent solution within the time available.
