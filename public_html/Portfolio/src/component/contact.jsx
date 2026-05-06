import React from "react";
import "./constact.css";

export const Contact = () => {
  return (
    <section className="contact" id="contact">

      {/* Title */}
      <div className="contact-header">
        <h1>
          Get In <span>Touch</span>
        </h1>
        <p>
          Have a project in mind or want to work together? Feel free to reach out.
        </p>
      </div>

      <div className="contact-container">

        {/* LEFT SIDE */}
        <div className="contact-left">
          <h3>Let’s Connect</h3>
          <p>
            I’m always open to discussing new projects, creative ideas or opportunities.
          </p>

          <div className="info">
            <div className="info-item">
              <span>📧</span>
              <div>
                <h4>Email</h4>
                <p>himanshubhatt050505@gmail.com</p>
              </div>
            </div>

            <div className="info-item">
              <span>📞</span>
              <div>
                <h4>Phone</h4>
                <p>+91 9027266480</p>
              </div>
            </div>

            <div className="info-item">
              <span>📍</span>
              <div>
                <h4>Location</h4>
                <p>Bhintal, Nainital, Uttarakhand</p>
              </div>
            </div>
          </div>
        </div>

        {/* RIGHT SIDE FORM */}
        <form 
          action="https://formspree.io/f/mpqkyogw" 
          method="POST" 
          className="contact-form"
        >
          <h3>Send a Message</h3>

          <div className="form-row">
            <input type="text" name="name" placeholder="Your Name" required />
            <input type="email" name="email" placeholder="Your Email" required />
          </div>

          <input type="text" name="subject" placeholder="Subject" required />
          <textarea name="message" placeholder="Your Message" required></textarea>

          <button type="submit">Send Message</button>
        </form>

      </div>

      {/* Bottom Box */}
      <div className="bottom-box">
        <div>
          <h3>Let’s build something amazing together!</h3>
          <p>I’m excited to hear about your ideas.</p>
        </div>

        <a href="/mycv.pdf" target="_blank" rel="noopener noreferrer">
          <button className="resume-btn">Download Resume</button>
        </a>
      </div>

    </section>
  );
};