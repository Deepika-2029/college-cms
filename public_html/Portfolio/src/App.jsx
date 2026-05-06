import {Header} from "./component/Header.jsx";
import { Home} from "./component/Home.jsx";
import{About} from "./component/About.jsx";
import {Skills} from "./component/skill.jsx";
import {Projects} from "./component/project.jsx";
import {Contact} from "./component/contact.jsx";
import {Footer} from "./component/footer.jsx";
export const App = () => {
  return (
    <div>
      <Header />
      <Home/>
      <About/>
      <Skills/>
      <Projects/>
      <Contact/>
      <Footer/>
    </div>
  );
};